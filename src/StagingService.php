<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use MediaWiki\Extension\Lakat\Domain\BucketRefType;
use MediaWiki\Extension\Lakat\Domain\BucketSchema;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\Page\DeletePageFactory;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use Status;
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

	private WikiPageFactory $wikiPageFactory;

	private DeletePageFactory $deletePageFactory;

	public function __construct(
		ILoadBalancer $loadBalancer,
		LakatStorage $lakatStorage,
		WikiPageFactory $wikiPageFactory,
		DeletePageFactory $deletePageFactory
	) {
		$this->loadBalancer = $loadBalancer;
		$this->lakatStorage = $lakatStorage;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->deletePageFactory = $deletePageFactory;
	}

	/**
	 * Retrieve a list of modified articles in the given branch, optionally filtered.
	 *
	 * @param string $branchName
	 * @param array|null $filterArticles Optionally filter articles by name
	 * @return StagedArticle[]
	 */
	public function getStagedArticles( string $branchName, array $filterArticles = null ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$conds = [ 'la_branch_name' => $branchName ];
		if ( $filterArticles !== null ) {
			$conds['la_name'] = $filterArticles;
		}
		$fields = ['la_branch_name', 'la_name', 'la_rev_id'];
		$rows = $dbr->select( self::TABLE, $fields, $conds, __METHOD__ );

		$res = [];
		foreach ( $rows as $row ) {
			$res[] = StagedArticle::fromRow( $row );
		}

		return $res;
	}

  
	public function isStaged( string $branchName, string $articleName ): bool {
		return (bool)$this->getStagedArticles( $branchName, [ $articleName ] );
	}

	/**
	 * Add article to the list of modified articles
	 *
	 * @param string $branchName
	 * @param string $articleName
	 * @param int|null $revId Id of the first modified revision
	 * @return void
	 */
	public function stage( string $branchName, string $articleName, ?int $revId ): void {

		$row = [
			'la_branch_name' => $branchName,
			'la_name' => $articleName,
			'la_rev_id' => $revId,
		];
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );
		$dbw->insert( self::TABLE, $row, __METHOD__, [ 'IGNORE' ] );
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
		foreach ( $this->getStagedArticles( $branchName, $articles ) as $stagedArticle ) {
			$articleName = $stagedArticle->articleName;
			$wikiPage = $this->wikiPageFactory->newFromTitle( Title::newFromText( "$branchName/$articleName" ) );
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

	/**
	 * Reset modified article to the state stored in Lakat storage
	 *
	 * @param User $user
	 * @param string $branchName
	 * @param string $articleName
	 * @return void
	 * @throws Exception
	 */
	public function reset( User $user, string $branchName, string $articleName ) {
		$page = $this->wikiPageFactory->newFromTitle( Title::newFromText( "$branchName/$articleName" ) );

		$branchId = LakatArticleMetadata::getBranchId( $branchName );
		$text = null;
		try {
			$text = $this->lakatStorage->getArticleFromArticleName( $branchId, $articleName );
		} catch ( Exception $e ) {
			// here we assume article doesn't exist on Lakat,
			// but it would be better to use more specific exception
		}

		if ($text === null) {
			// article doesn't exist on Lakat - delete page
			$deletePage = $this->deletePageFactory->newDeletePage( $page, $user );
			$message = wfMessage( 'staging-reset-comment' )->inContentLanguage()->text();
			$status = $deletePage->deleteUnsafe( $message );
			if ( !$status->isOK() ) {
				throw new Exception( 'Failed to delete page: ' . Status::wrap( $status )->getWikiText() );
			}
		} else {
			// store article text in page - update page
			$content = ContentHandler::makeContent( $text, $page->getTitle() );
			$message = wfMessage( 'staging-reset-comment' )->inContentLanguage()->text();
			$comment = CommentStoreComment::newUnsavedComment( $message );
			$pageUpdater = $page->newPageUpdater( $user )->setContent( SlotRecord::MAIN, $content );
			$flags = EDIT_INTERNAL | EDIT_SUPPRESS_RC;
			$pageUpdater->saveRevision( $comment, $flags );
			if ( !$pageUpdater->wasSuccessful() ) {
				throw new Exception( 'Failed to update page: ' . $pageUpdater->getStatus()->getWikiText() );
			}
		}

		// now article is in sync with Lakat storage, unstage it
		$this->unstage( $branchName, $articleName );
	}

	/**
	 * @param User $user
	 * @param string $branch
	 * @param array $articles
	 * @return void
	 * @throws Exception
	 */
	public function resetStaged( User $user, string $branch, array $articles ) {
		foreach ( $articles as $article ) {
			$this->reset( $user, $branch, $article );
		}
	}
}
