<?php

namespace MediaWiki\Extension\Lakat\Domain;

class AtomicBucket extends Bucket {
	private string $data;

	public static function getSchema() : int {
		return BucketSchema::DEFAULT_ATOMIC;
	}

	public function __construct(string $data) {
		$this->data = $data;
	}

	public function data(): string {
		return $this->data;
	}
}
