<?php

namespace MediaWiki\Extension\Lakat;

use Wikimedia\Rdbms\ILoadBalancer;

class StagingService {
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
}
