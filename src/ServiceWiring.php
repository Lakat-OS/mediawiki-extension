<?php

use MediaWiki\Extension\Lakat\LakatServices;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	LakatStorage::SERVICE_NAME => static function ( MediaWikiServices $services ): LakatStorage {
		return new LakatStorage(
			$services->getConfigFactory()->makeConfig( 'lakat' )->get( 'LakatRpcUrl' ),
			LoggerFactory::getInstance( 'Lakat' ),
			$services->getGlobalIdGenerator(),
			$services->getHttpRequestFactory()
		);
	},
	StagingService::SERVICE_NAME => static function ( MediaWikiServices $services ): StagingService {
		return new StagingService(
			$services->getDBLoadBalancer(),
			LakatServices::getLakatStorage( $services ),
			$services->getWikiPageFactory(),
			$services->getDeletePageFactory()
		);
	},
];
