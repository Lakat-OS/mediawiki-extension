<?php

namespace MediaWiki\Extension\Lakat;

use Wikimedia\Rdbms\ILoadBalancer;

class StagingService {
	public const SERVICE_NAME = 'LakatStagingService';

	private const TABLE = 'lakat_article';

	private ILoadBalancer $loadBalancer;

	public function __construct(ILoadBalancer $loadBalancer) {
		$this->loadBalancer = $loadBalancer;
	}

	public function listModifiedArticles( string $branchName ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$res = $dbr->select('lakat_article', 'la_name', ['la_branch_name' => $branchName], __METHOD__);
		$rows = [];
		foreach ($res as $row) {
			$rows[] = $row->la_name;
		}
		return $rows;
	}

	public function stageArticle( string $branchName, string $articleName ) {
		$row = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
			'la_last_rev_id' => -1,
			'la_sync_rev_id' => null,
		];
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->insert( self::TABLE, $row, __METHOD__ );
	}
}
