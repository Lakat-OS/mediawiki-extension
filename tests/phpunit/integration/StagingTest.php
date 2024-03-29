<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration;

use CommentStoreComment;
use ContentHandler;
use FormatJson;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\LakatServices;
use MediaWiki\Extension\Lakat\StagedArticle;
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
	 * @covers ::getStagedArticles
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
		$this->assertEquals( [$articleName], array_map( fn( StagedArticle $article ) => $article->articleName, $articles ));

		// 3. submit staged articles
		$this->stagingService->submitStaged( $this->getUser(), $branchName, [$articleName], 'Test submit' );

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
		$this->assertEquals( [$articleName], array_map( fn( StagedArticle $article ) => $article->articleName, $articles ));

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

	/**
	 * @covers ::reset
	 */
	public function testResetEditedArticle() {
		// 1. create branch
		$branchName = 'Test branch ResetNewArticle ' . microtime( true );
		$this->createBranch( $branchName );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// 2. create article in the branch
		$articleName = 'Test article';
		$articleText = 'Test content';
		$updateStatus = $this->editPage( "$branchName/$articleName", $articleText );
		$this->assertTrue( $updateStatus->isGood() );

		// check one article staged
		$articles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [$articleName], array_map( fn( StagedArticle $article ) => $article->articleName, $articles ));

		// check article content is saved
		$content = $this->getExistingTestPage( "$branchName/$articleName" )->getContent();
		$this->assertEquals( $articleText, $content->serialize() );

		// 3. submit staged articles
		$this->stagingService->submitStaged( $this->getUser(), $branchName, [$articleName], 'Test submit' );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// 4. edit article
		$modifiedArticleText = 'Modified test content';
		$updateStatus = $this->editPage( "$branchName/$articleName", $modifiedArticleText );
		$this->assertTrue( $updateStatus->isGood() );

		// check article content changed and staged
		$content = $this->getExistingTestPage( "$branchName/$articleName" )->getContent();
		$this->assertEquals( 'Modified test content', $content->serialize() );
		$articles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [$articleName], array_map( fn( StagedArticle $article ) => $article->articleName, $articles ));

		// 5. reset article modifications
		$this->stagingService->reset( $this->getUser(), $branchName, $articleName );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// check article content is reset to previous version
		$content = $this->getExistingTestPage( "$branchName/$articleName" )->getContent();
		$this->assertEquals( "\n" . $articleText, $content->serialize() );
	}

	/**
	 * @covers ::submitStaged
	 */
	public function testStageNewArticle() {
		// 1. create article with two sections
		$branchName = 'BranchX';
		$this->createBranch( $branchName );

		$section1Name = 'Section 1';
		$section1Content = 'Content 1';
		$updateStatus = $this->editPage( "$branchName/$section1Name", $section1Content );
		$this->assertTrue( $updateStatus->isGood() );

		$section2Name = 'Section 2';
		$section2Content = 'Content 2';
		$updateStatus = $this->editPage( "$branchName/$section2Name", $section2Content );
		$this->assertTrue( $updateStatus->isGood() );

		$articleName = 'Test article';
		$content = "{{:$branchName/$section1Name}}{{:$branchName/$section2Name}}";
		$updateStatus = $this->editPage( "$branchName/$articleName", $content );
		$this->assertTrue( $updateStatus->isGood() );

		// 1.1. check everything staged
		$this->assertTrue($this->stagingService->isStaged( $branchName, $section1Name ));
		$this->assertTrue($this->stagingService->isStaged( $branchName, $section2Name ));
		$this->assertTrue($this->stagingService->isStaged( $branchName, $articleName ));

		// 2. submit article to Lakat
		$this->stagingService->submitStaged( $this->getUser(), $branchName, [$articleName], 'Test submit' );

		// 2.1. check nothing staged
		$this->assertFalse($this->stagingService->isStaged( $branchName, $section1Name ));
		$this->assertFalse($this->stagingService->isStaged( $branchName, $section2Name ));
		$this->assertFalse($this->stagingService->isStaged( $branchName, $articleName ));
	}

	private function createBranch( string $branchName ): void {
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
