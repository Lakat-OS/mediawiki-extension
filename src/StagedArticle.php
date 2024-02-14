<?php

namespace MediaWiki\Extension\Lakat;

class StagedArticle {
	public string $branchName;
	public string $articleName;
	public ?int $revId;

	public function __construct(
		string $branchName,
		string $articleName,
		?int $revId
	) {
		$this->branchName = $branchName;
		$this->articleName = $articleName;
		$this->revId = $revId;
	}

	public static function fromRow( object $row ): StagedArticle {
		return new StagedArticle(
			$row->la_branch_name,
			$row->la_name,
			$row->la_rev_id
		);
	}
}
