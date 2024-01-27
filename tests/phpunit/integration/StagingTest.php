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

		$this->stagingService = StagingService::getInstance();

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
	 * @covers ::listArticles
	 */
	public function testListModifiedArticlesEmpty(): void {
		$modifiedArticles = $this->stagingService->listModifiedArticles();

		$this->assertIsArray( $modifiedArticles );
		$this->assertEmpty( $modifiedArticles );
	}

	/**
	 * @covers ::listArticles
	 */
	public function testNewArticleInModifiedArticles() {
		$branchName = 'Test branch ' . microtime(true);
		$this->createBranch($branchName);
		$articleName = 'Test article ' . microtime(true);
		$this->createArticle($branchName, $articleName, 'Test content', 'Test commit');
//		$branchId = $this->rpc->createGenesisBranch( BranchType::TWIG, $branchName, '', true, 'Create test branch' );
//		$contents = [
//			[
//				"data" => 'Test content ' . microtime(true),
//				"schema" => BucketSchema::DEFAULT_ATOMIC,
//				"parent_id" => '',
//				"signature" => '',
//				"refs" => []
//			],
//			[
//				"data" => [
//					"order" => [ [ "id" => 1, "type" => BucketRefType::NO_REF ] ],
//					"name" => 'Test article ' . microtime(true)
//				],
//				"schema" => BucketSchema::DEFAULT_MOLECULAR,
//				"parent_id" => '',
//				"signature" => '',
//				"refs" => []
//			]
//		];
//		$submitData = $this->rpc->submitContentToTwig( $branchId, $contents, '', '', 'Submit test article' );

//		$articles = $this->stagingService->listModifiedArticles();
		$articles = $this->listModifiedArticles( $branchName );

		$this->assertIsArray( $articles );
		$this->assertContains( $articleName, $articles );
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

		$this->addToStaging($branchName, $articleName);
		// when submitted:
		// 1. save to remote
		// 2. removeFromStaging($branchName, $articleName);
		// when reverted:
		// 1. fetch from remote
		// 2. save locally
		// 2. removeFromStaging($branchName, $articleName);
	}

	private function getUser(): User {
		return $this->getTestUser()->getUser();
	}

	private function listModifiedArticles( string $branchName ): array {
		$res = $this->getDb()->select('lakat_article', 'la_name', '', __METHOD__);
		$rows = [];
		foreach ($res as $row) {
			$rows[] = $row->la_name;
		}
		return $rows;
	}

	private function addToStaging( string $branchName, string $articleName ) {
		$row = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
			'la_last_rev_id' => -1,
			'la_sync_rev_id' => null,
		];
		$this->getDb()->insert( 'lakat_article', $row, __METHOD__ );
	}
}
