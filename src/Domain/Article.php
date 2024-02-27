<?php

namespace MediaWiki\Extension\Lakat\Domain;

use WikiPage;

class Article {
	private string $name;

	/**
	 * @var Bucket[]
	 */
	private array $buckets = [];

	public function __construct( string $name ) {
		$this->name = $name;
	}

	public static function fromWikiPage( WikiPage $page ): Article {
		$name = $page->getTitle()->getSubpageText();
		$text = $page->getContent()->serialize();
		return self::fromWikiText( $name, $text );
	}

	public static function fromWikiText( string $name, string $text ): Article {
		$t = new self( $name );

		/**
		 * Split Article into array of Buckets, e.g.:
		 *  aaa				--> AtomicBucket
		 *  {{:Article1}}	--> MolecularBucket
		 *  bbb				--> AtomicBucket
		 */
		preg_match_all('/\{\{:[^}]+?}}/', $text, $matches, PREG_OFFSET_CAPTURE);
		$pos = 0;
		foreach ($matches[0] as $match) {
			$ref = $match[0];
			$offset = $match[1];
			if ($pos < $offset) {
				$t->buckets[] = new AtomicBucket( substr( $text, $pos, $offset - $pos ) );
			}
			$t->buckets[] = new MolecularBucket( $ref );
			$pos = $offset + strlen( $ref );
		}
		if ( $pos < strlen( $text ) ) {
			$t->buckets[] = new AtomicBucket( substr( $text, $pos ) );
		}

		return $t;
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
}
