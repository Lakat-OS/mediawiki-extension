<?php

namespace MediaWiki\Extension\Lakat\Domain;

class MolecularBucket extends Bucket {
	private string $name;

	/**
	 * @var Bucket[]
	 */
	private array $buckets = [];

	public static function getSchema(): int {
		return BucketSchema::DEFAULT_MOLECULAR;
	}

	public function __construct( string $name ) {
		$this->name = $name;
	}

	public function name(): string {
		return $this->name;
	}

	/**
	 * @return Bucket[]
	 */
	public function buckets(): array {
		return $this->buckets;
	}

	public function addBucket( Bucket $bucket ): void {
		$this->buckets[] = $bucket;
	}
}
