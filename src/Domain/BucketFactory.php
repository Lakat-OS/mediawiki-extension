<?php

namespace MediaWiki\Extension\Lakat\Domain;

use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use WikiPage;

class BucketFactory {

	const SERVICE_NAME = 'LakatBucketFactory';

	private WikiPageFactory $wikiPageFactory;

	public function __construct( WikiPageFactory $wikiPageFactory) {
		$this->wikiPageFactory = $wikiPageFactory;
	}

	public function fromWikiPage( WikiPage $page ): MolecularBucket {
		$name = $page->getTitle()->getSubpageText();
		$text = $page->getContent()->serialize();
		return self::fromWikiText( $name, $text );
	}

	public function fromWikiText( string $name, string $text ): MolecularBucket {
		$t = new MolecularBucket( $name );

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
				$textBeforeRef = substr( $text, $pos, $offset - $pos );
				$t->addBucket( new AtomicBucket( $textBeforeRef ) );
			}
			$refName = substr( $ref, 3, -2 );
			$molecularBucket = $this->loadMolecularBucket( $refName );
			$t->addBucket( $molecularBucket );
			// shift position to the length of the reference
			$pos = $offset + strlen( $ref );
		}
		// process text after the last reference
		if ( $pos < strlen( $text ) ) {
			$textEnding = substr( $text, $pos );
			$t->addBucket( new AtomicBucket( $textEnding ) );
		}

		return $t;
	}

	private function loadMolecularBucket( string $name ): MolecularBucket {
		$title = Title::newFromText( $name );
		$page = $this->wikiPageFactory->newFromTitle( $title );
		return $this->fromWikiPage( $page );
	}
}
