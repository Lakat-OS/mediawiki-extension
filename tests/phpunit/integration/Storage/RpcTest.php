<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration\Storage;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Lakat\Domain\BranchType;
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

		$branchId = LakatStorageRPC::getInstance()->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg );

		$this->assertNotEmpty($branchId);

		return compact('branchId', 'branchName');
	}

	/**
	 * @covers ::getBranchNameFromBranchId
	 *
	 * @depends testCreateGenesisBranch
	 */
	public function testGetBranchNameFromBranchId( array $branchData ) {
		extract($branchData);

		$resultBranchName = LakatStorageRPC::getInstance()->getBranchNameFromBranchId($branchId);

		$this->assertEquals($branchName, $resultBranchName);
	}
}
