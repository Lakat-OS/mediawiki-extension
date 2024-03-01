<?php

namespace MediaWiki\Extension\Lakat\Tests\Integration;

use MediaWiki\Extension\Lakat\Domain\AtomicBucket;
use MediaWiki\Extension\Lakat\Domain\BucketFactory;
use MediaWiki\Extension\Lakat\Domain\MolecularBucket;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @coversDefaultClass \MediaWiki\Extension\Lakat\Domain\BucketFactory
 */
class BucketFactoryTest extends MediaWikiIntegrationTestCase {
	private WikiPageFactory $wikiPageFactory;

	private BucketFactory $bucketFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->wikiPageFactory = $this->getServiceContainer()->getWikiPageFactory();
		$this->bucketFactory = $this->getServiceContainer()->get(BucketFactory::SERVICE_NAME);
	}

	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_empty() {
		$name = 'Test article';
		$article = $this->bucketFactory->fromWikiText( $name, '' );

		$this->assertEquals( $name, $article->name() );
		$this->assertCount( 0, $article->buckets() );
	}

	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_simple() {
		$article = $this->bucketFactory->fromWikiText( 'Test article', 'Simple content');

		$buckets = $article->buckets();
		$this->assertCount( 1, $buckets );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[0] );
		$this->assertEquals( 'Simple content', $buckets[0]->data() );
	}

	/**
	 * @covers ::fromWikiText
	 */
	public function testArticleFromWikiText_ref() {
		$this->assertTrue($this->editPage('Section1', 'Content1')->isGood());
		$this->assertTrue($this->editPage('Section2', 'Content2')->isGood());

		$article = $this->bucketFactory->fromWikiText(
			'Test article',
			'aaa{{:Section1}}bbb{{:Section2}}ccc'
		);

		$buckets = $article->buckets();
		$this->assertCount( 5, $buckets );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[0] );
		$this->assertEquals( 'aaa', $buckets[0]->data() );
		$this->assertInstanceOf( MolecularBucket::class, $buckets[1] );
		$this->assertEquals( 'Section1', $buckets[1]->name() );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[2] );
		$this->assertEquals( 'bbb', $buckets[2]->data() );
		$this->assertInstanceOf( MolecularBucket::class, $buckets[3] );
		$this->assertEquals( 'Section2', $buckets[3]->name() );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[4] );
		$this->assertEquals( 'ccc', $buckets[4]->data() );
	}

	/**
	 * @covers ::fromWikiPage
	 */
	public function testArticleFromWikiPage() {
		$name = 'Article';
		$this->assertTrue( $this->editPage( $name, 'Content' )->isGood() );

		$title = Title::newFromText( $name );
		$page = $this->wikiPageFactory->newFromTitle( $title );
		$article = $this->bucketFactory->fromWikiPage( $page );

		$buckets = $article->buckets();
		$this->assertCount( 1, $buckets );
		$this->assertInstanceOf( AtomicBucket::class, $buckets[0] );
		$this->assertEquals( 'Content', $buckets[0]->data() );
	}
}
