<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration\Storage;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\Domain\BucketIdType;
use MediaWiki\Extension\Lakat\Domain\BucketSchema;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;

/**
 * @group rpc
 * @coversDefaultClass LakatStorageRPC
 */
class RpcTest extends MediaWikiIntegrationTestCase {
	private LakatStorageRPC $rpc;

	protected function setUp() : void {
		parent::setUp();

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

		$this->rpc = LakatStorageRPC::getInstance();
	}

	/**
	 * @covers ::createGenesisBranch
	 */
	public function testCreateGenesisBranch() : array {
		$branchType = BranchType::TWIG;
		$branchName = 'Test branch '.microtime(true);
		$signature = "\x00\x10\x20\x30";
		$acceptConflicts = true;
		$msg = 'Create genesis test branch';

		$branchId = $this->rpc->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg );

		$this->assertNotEmpty($branchId);

		return compact('branchId', 'branchName');
	}

	/**
	 * @covers ::getBranchNameFromBranchId
	 *
	 * @depends testCreateGenesisBranch
	 */
	public function testGetBranchNameFromBranchId( array $branchData ) {
		$branchId = $branchData['branchId'];
		$branchName = $branchData['branchName'];

		$retrievedBranchName = $this->rpc->getBranchNameFromBranchId($branchId);

		$this->assertEquals($branchName, $retrievedBranchName);
	}

	/**
	 * @covers ::submitContentToTwig
	 *
	 * @depends testCreateGenesisBranch
	 */
	public function testSubmitContentToTwig( array $branchData ) {
		$branchId = $branchData['branchId'];

		$articleName = "Article Name " . microtime(true);
		$articlePart1 = "First bucket data " . microtime(true);
		$articlePart2 = "Second bucket data " . microtime( true );
		$contents = [
			[
				"data" => $articlePart1,
				"schema" => BucketSchema::DEFAULT_ATOMIC,
				"parent_id" => base64_encode(''),
				"signature" => base64_encode("\x00"),
				"refs" => []
			],
			[
				"data" => $articlePart2,
				"schema" => BucketSchema::DEFAULT_ATOMIC,
				"parent_id" => base64_encode(''),
				"signature" => base64_encode("\x00"),
				"refs" => []
			],
			[
				"data" => [
					"order" => [
						["id" => 0, "type" => BucketIdType::NO_REF],
						["id" => 1, "type" => BucketIdType::NO_REF]],
					"name" => $articleName
				],
				"schema" => BucketSchema::DEFAULT_MOLECULAR,
				"parent_id" => base64_encode(''),
				"signature" => base64_encode("\x00"),
				"refs" => []
			]
		];

		$publicKey = base64_encode(random_bytes(10));
		$proof = base64_encode(random_bytes(10));
		$msg = 'submit content ' . microtime(true);

		$submitData = $this->rpc->submitContentToTwig( $branchId, $contents, $publicKey, $proof, $msg );

		$this->assertNotEmpty( $submitData );
		$this->assertIsArray( $submitData );
		$this->assertEquals( $branchId, $submitData['branch_id'] );
		$this->assertCount( 3, $submitData['bucket_refs'] );
		$this->assertEquals( $articleName, $submitData['registered_names'][0]['name'] );

		return compact('branchId', 'articleName', 'articlePart1', 'articlePart2');
	}

	/**
	 * @covers ::submitContentToTwig
	 *
	 * @depends testSubmitContentToTwig
	 */
	public function testGetArticleFromArticleName( array $articleData ) {
		$branchId = $articleData['branchId'];
		$articleName = $articleData['articleName'];
		$articlePart1 = $articleData['articlePart1'];
		$articlePart2 = $articleData['articlePart2'];

		$submitData = $this->rpc->getArticleFromArticleName( $branchId, $articleName );

		$this->assertEquals( "\n$articlePart1\n$articlePart2", $submitData );
	}
}
