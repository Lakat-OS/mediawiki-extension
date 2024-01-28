<?php

use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\MediaWikiServices;

return [
	StagingService::SERVICE_NAME => static function ( MediaWikiServices $services ): StagingService {
		return new StagingService( $services->getDBLoadBalancer() );
	},
];
