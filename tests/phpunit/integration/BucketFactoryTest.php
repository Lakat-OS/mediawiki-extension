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
		$this->bucketFactory = $this->getServiceContainer()->get( BucketFactory::SERVICE_NAME );
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

		$section1 = $buckets[1];
		$this->assertInstanceOf( MolecularBucket::class, $section1 );
		$this->assertEquals( 'Section1', $section1->name() );
		$buckets1 = $section1->buckets();
		$this->assertCount( 1, $buckets1 );
		$this->assertInstanceOf( AtomicBucket::class, $buckets1[0] );
		$this->assertEquals( 'Content1', $buckets1[0]->data() );

		$this->assertInstanceOf( AtomicBucket::class, $buckets[2] );
		$this->assertEquals( 'bbb', $buckets[2]->data() );

		$section2 = $buckets[3];
		$this->assertInstanceOf( MolecularBucket::class, $section2 );
		$this->assertEquals( 'Section2', $section2->name() );
		$buckets2 = $section2->buckets();
		$this->assertCount( 1, $buckets2 );
		$this->assertInstanceOf( AtomicBucket::class, $buckets2[0] );
		$this->assertEquals( 'Content2', $buckets2[0]->data() );

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
