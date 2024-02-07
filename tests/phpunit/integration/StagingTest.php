<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration;

use CommentStoreComment;
use ContentHandler;
use FormatJson;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\LakatServices;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Title;
use User;

/**
 * @group Database
 * @coversDefaultClass StagingService
 */
class StagingTest extends MediaWikiIntegrationTestCase {
	private StagingService $stagingService;

	private LakatStorage $lakatStorage;

	protected function setUp(): void {
		parent::setUp();

		$services = $this->getServiceContainer();

		// use HttpRequestFactory instead of NullHttpRequestFactory to test RPC calls
		$services->resetServiceForTesting('HttpRequestFactory');
		$services->redefineService(
			'HttpRequestFactory',
			static function ( MediaWikiServices $services ) {
				return new HttpRequestFactory(
					new ServiceOptions( HttpRequestFactory::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
					new NullLogger()
				);
			}
		);

		$this->stagingService = LakatServices::getStagingService( $services );
		$this->lakatStorage = LakatServices::getLakatStorage( $services );
	}

	/**
	 * @covers ::listModifiedArticles
	 */
	public function testListModifiedArticles() {
		// 1. create branch
		$branchName = 'Test branch ' . microtime(true);
		$this->createBranch($branchName);

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// 2. create article in the branch
		$articleName = 'Test article';
		$this->getExistingTestPage("$branchName/$articleName");

		// check one article staged
		$articles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [$articleName], $articles );

		// 3. submit staged articles
		$this->stagingService->submitStaged( $this->getUser(), $branchName, $articles, 'Test submit' );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );
	}

	/**
	 * @covers ::reset
	 */
	public function testResetNewArticle() {
		// 1. create branch
		$branchName = 'Test branch ResetNewArticle ' . microtime( true );
		$this->createBranch( $branchName );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// 2. create article in the branch
		$articleName = 'Test article';
		$articleText = 'Test content';
		$page = $this->getExistingTestPage( "$branchName/$articleName" );
		$this->editPage( $page, $articleText );

		// check one article staged
		$articles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [ $articleName ], $articles );

		// check article content is saved
		$content = $page->getContent();
		$this->assertEquals( $articleText, $content->serialize() );

		// 3. reset article
		$this->stagingService->reset( $this->getUser(), $branchName, $articleName );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// check article page is deleted
		$page =
			MediaWikiServices::getInstance()
				->getWikiPageFactory()
				->newFromTitle( Title::newFromText( "$branchName/$articleName" ) );
		$this->assertFalse( $page->exists() );
	}

	private function createBranch( string $branchName ) {
//		$specialPageFactory = $this->getServiceContainer()->getSpecialPageFactory();
//		$specialPageFactory->getPage( 'CreateBranch' )->execute();
		// create branch remotely
		$branchType = BranchType::TWIG;
		$signature = '';
		$acceptConflicts = true;
		$msg = 'Test create genesis branch';
		$branchId = $this->lakatStorage->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg);

		// fetch branch data from remote
		$data = $this->lakatStorage->getBranchDataFromBranchId( $branchId, false );

		// create branch page
		$title = Title::newFromText( $branchName );
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( FormatJson::encode( $data ), $title, CONTENT_MODEL_JSON );
		$comment = CommentStoreComment::newUnsavedComment(
			wfMessage( 'createbranch-revision-comment' )->inContentLanguage()->text()
		);
		$page->newPageUpdater( $this->getUser() )
			->setContent( SlotRecord::MAIN, ContentHandler::makeContent('Branch root page', $title) )
			->setContent( 'lakat', $content )
			->saveRevision( $comment );
	}

	private function getUser(): User {
		return $this->getTestUser()->getUser();
	}
}
