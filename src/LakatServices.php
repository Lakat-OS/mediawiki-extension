<?php

namespace MediaWiki\Extension\Lakat;

use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;

class LakatServices {
	public static function getLakatStorage( ContainerInterface $services = null ): LakatStorage {
		return ( $services ?? MediaWikiServices::getInstance() )->getService( LakatStorage::SERVICE_NAME );
	}

	public static function getStagingService( ContainerInterface $services = null ): StagingService {
		return ( $services ?? MediaWikiServices::getInstance() )->getService( StagingService::SERVICE_NAME );
	}
}
