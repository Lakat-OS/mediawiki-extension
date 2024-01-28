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
 * @group staging
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
		$modifiedArticles = $this->stagingService->listModifiedArticles( $branchName );
		$this->assertEquals( [], $modifiedArticles );

		// 2. create article
		$articleName = 'Test article ' . microtime(true);
		$this->createArticle($branchName, $articleName, 'Test content', 'Test commit');

		// check one article staged
		$articles = $this->stagingService->listModifiedArticles( $branchName );
		$this->assertEquals( [$articleName], $articles );

		// 3. submit article
		$this->submitArticle($branchName, $articleName);

		// check nothing staged
		$modifiedArticles = $this->stagingService->listModifiedArticles( $branchName );
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

	private function createArticle( string $branchName, string $articleName, string $text, string $summary ) {
		$title = Title::newFromText( "$branchName/$articleName" );
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( $text, $title );
		$comment = CommentStoreComment::newUnsavedComment( $summary );
		$page->newPageUpdater( $this->getUser() )
			->setContent( SlotRecord::MAIN, $content )
			->saveRevision( $comment );

		// when submitted:
		// 1. save to remote
		// 2. removeFromStaging($branchName, $articleName);
		// when reverted:
		// 1. fetch from remote
		// 2. save locally
		// 2. removeFromStaging($branchName, $articleName);
	}

	private function submitArticle( string $branchName, string $articleName ) {
		$this->removeFromStaging( $branchName, $articleName );
	}

	private function getUser(): User {
		return $this->getTestUser()->getUser();
	}

	private function removeFromStaging( string $branchName, string $articleName ) {
		$conds = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
		];
		$this->getDb()->delete( 'lakat_article', $conds, __METHOD__ );
	}
}
