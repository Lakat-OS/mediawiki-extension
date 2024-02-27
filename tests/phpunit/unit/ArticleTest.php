<?php

namespace MediaWiki\Extension\Lakat\Tests\Unit;

use MediaWiki\Extension\Lakat\Domain\Article;
use MediaWiki\Extension\Lakat\Domain\AtomicBucket;
use MediaWiki\Extension\Lakat\Domain\MolecularBucket;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\Lakat\Domain\Article
 */
class ArticleTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_empty() {
		$name = 'Test article';
		$article = Article::fromWikiText( $name, '' );

		$this->assertEquals( $name, $article->name() );
		$this->assertCount( 0, $article->buckets() );
	}

	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_simple() {
		$article = Article::fromWikiText( 'Test article', 'Simple content');

		$buckets = $article->buckets();
		$this->assertCount( 1, $buckets );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[0] );
		$this->assertEquals( 'Simple content', $buckets[0]->data() );
	}

	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_ref() {
		$article = Article::fromWikiText(
			'Test article',
			'aaa{{:Article1}}bbb{{:Article2}}ccc'
		);

		$buckets = $article->buckets();
		$this->assertCount( 5, $buckets );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[0] );
		$this->assertEquals( 'aaa', $buckets[0]->data() );
		$this->assertInstanceOf( MolecularBucket::class, $buckets[1] );
		$this->assertEquals( '{{:Article1}}', $buckets[1]->ref() );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[2] );
		$this->assertEquals( 'bbb', $buckets[2]->data() );
		$this->assertInstanceOf( MolecularBucket::class, $buckets[3] );
		$this->assertEquals( '{{:Article2}}', $buckets[3]->ref() );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[4] );
		$this->assertEquals( 'ccc', $buckets[4]->data() );
	}
}
