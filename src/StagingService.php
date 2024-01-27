<?php

namespace MediaWiki\Extension\Lakat;

class StagingService {
	private static StagingService $instance;

	public static function getInstance(): StagingService {
		return self::$instance ?? self::$instance = new self();
	}

	public function listModifiedArticles(): array {
		return [];
	}
}
