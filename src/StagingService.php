<?php

namespace MediaWiki\Extension\Lakat;

use Exception;
use MediaWiki\Extension\Lakat\Domain\BucketRefType;
use MediaWiki\Extension\Lakat\Domain\BucketSchema;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * When submitted:
 * 1. save to lakat storage
 * 2. unstage($branchName, $articleName);
 * When reverted:
 * 1. revert locally to sync_rev_id
 * 2. removeFromStaging($branchName, $articleName);
 */
class StagingService {
	public const SERVICE_NAME = 'LakatStagingService';

	public const TABLE = 'lakat_staging';

	private ILoadBalancer $loadBalancer;

	private LakatStorage $lakatStorage;

	public function __construct( ILoadBalancer $loadBalancer, LakatStorage $lakatStorage ) {
		$this->loadBalancer = $loadBalancer;
		$this->lakatStorage = $lakatStorage;
	}

	/**
	 * Retrieve a list of modified articles in the given branch, optionally filtered.
	 *
	 * @param string $branchName
	 * @param array|null $filterArticles Optionally filter articles by name
	 * @return string[] List of article names
	 */
	public function getStagedArticles( string $branchName, array $filterArticles = null ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$conds = [ 'la_branch_name' => $branchName ];
		if ($filterArticles !== null) {
			$conds['la_name'] = $filterArticles;
		}
		$res = $dbr->select( self::TABLE, 'la_name', $conds, __METHOD__ );
		$rows = [];
		foreach ( $res as $row ) {
			$rows[] = $row->la_name;
		}

		return $rows;
	}

	/**
	 * Add article to the list of modified articles
	 *
	 * @param string $branchName
	 * @param string $articleName
	 * @return void
	 */
	public function stage( string $branchName, string $articleName ) {
		$row = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
		];
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->insert( self::TABLE, $row, __METHOD__ );
	}

	/**
	 * Remove article from the list of modified articles
	 *
	 * @param string $branchName
	 * @param string $articleName
	 * @return void
	 */
	public function unstage( string $branchName, string $articleName): void {
		$conds = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
		];
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->delete( self::TABLE, $conds, __METHOD__ );
	}

	/**
	 * Submit selected articles to Lakat
	 *
	 * @param User $user
	 * @param string $branchName
	 * @param array $articles
	 * @param string $msg
	 * @return void
	 * @throws Exception
	 */
	public function submitStaged( User $user, string $branchName, array $articles, string $msg ) {
		$branchId = LakatArticleMetadata::getBranchId( $branchName );
		foreach ( $this->getStagedArticles( $branchName, $articles ) as $articleName ) {
			$wikiPage =
				MediaWikiServices::getInstance()
					->getWikiPageFactory()
					->newFromTitle( Title::newFromText( "$branchName/$articleName" ) );
			try {
				$submitData = LakatArticleMetadata::load( $wikiPage );
				$bucketRefs = $submitData['bucket_refs'];
			}
			catch ( Exception $e ) {
				$bucketRefs = [ '', '' ];
			}

			$contents = [
				[
					"data" => $wikiPage->getContent()->serialize(),
					"schema" => BucketSchema::DEFAULT_ATOMIC,
					"parent_id" => $bucketRefs[0],
					"signature" => base64_encode( '' ),
					"refs" => [],
				],
				[
					"data" => [
						"order" => [
							[ "id" => 0, "type" => BucketRefType::NO_REF ],
						],
						"name" => $articleName,
					],
					"schema" => BucketSchema::DEFAULT_MOLECULAR,
					"parent_id" => $bucketRefs[1],
					"signature" => base64_encode( '' ),
					"refs" => [],
				],
			];
			$publicKey = '';
			$proof = '';

			$submitData = $this->lakatStorage->submitContentToTwig( $branchId, $contents, $publicKey, $proof, $msg );
			// update page metadata
			LakatArticleMetadata::save( $wikiPage, $user, $submitData );

			$this->unstage( $branchName, $articleName );
		}
	}
}
