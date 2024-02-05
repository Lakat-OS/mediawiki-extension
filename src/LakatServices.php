<?php

namespace MediaWiki\Extension\Lakat;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;

class LakatServices {
	public static function getStagingService( ContainerInterface $services = null ): StagingService {
		return ( $services ?? MediaWikiServices::getInstance() )->getService( StagingService::SERVICE_NAME );
	}
}
