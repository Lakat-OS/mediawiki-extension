<?php

namespace MediaWiki\Extension\Lakat\Domain;

class MolecularBucket extends Bucket {
	private string $ref;

	public static function getSchema(): int {
		return BucketSchema::DEFAULT_MOLECULAR;
	}

	public function __construct( string $ref ) {
		$this->ref = $ref;
	}

	public function ref(): string {
		return $this->ref;
	}
}
