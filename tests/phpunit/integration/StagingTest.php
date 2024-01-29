<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration;

use CommentStoreComment;
use ContentHandler;
use FormatJson;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
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

	protected function setUp(): void {
		parent::setUp();

		$services = $this->getServiceContainer();
		$loadBalancer = $services->getDBLoadBalancer();
		$this->stagingService = new StagingService( $loadBalancer );

		// use HttpRequestFactory instead of NullHttpRequestFactory to test RPC calls
		$services = MediaWikiServices::getInstance();
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
		$this->stagingService->submitStaged( $this->getUser(), $branchName, 'Test submit' );

		// check nothing staged
		$modifiedArticles = $this->stagingService->getStagedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );
	}

	private function createBranch( string $branchName ) {
//		$specialPageFactory = $this->getServiceContainer()->getSpecialPageFactory();
//		$specialPageFactory->getPage( 'CreateBranch' )->execute();
		// create branch remotely
		$branchType = BranchType::TWIG;
		$signature = '';
		$acceptConflicts = true;
		$msg = 'Test create genesis branch';
		$branchId = LakatStorageRPC::getInstance()->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg);

		// fetch branch data from remote
		$data = LakatStorageRPC::getInstance()->getBranchDataFromBranchId( $branchId, false );

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
