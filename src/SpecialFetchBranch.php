<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use FormatJson;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use RedirectSpecialArticle;
use Title;

class SpecialFetchBranch extends RedirectSpecialArticle {
	public function __construct() {
		parent::__construct( 'FetchBranch' );
	}

	protected function getGroupName(): string {
		return 'lakat';
	}

	/**
	 * @param string|null $subpage
	 * @return Title|bool
	 */
	public function getRedirect( $subpage ) {
		return $this->branchPageTitle( $subpage );
	}

	/**
	 * @param string $branchId
	 * @return Title|bool
	 */
	private function branchPageTitle( string $branchId ) {
		if (!$branchId) {
			return false;
		}

		$branchName = LakatStorageRPC::getInstance()->getBranchNameFromBranchId( $branchId );

		$title = Title::newFromText( $branchName );

		// if branch page already exists, just return it
		if ( $title->isKnown() ) {
			return $title;
		}

		// check if it's possible to create wiki page with this name
		if ( !$title->canExist() ) {
			throw new Exception('Invalid branch name');
		}

		// fetch branch data from remote
		$data = LakatStorageRPC::getInstance()->getBranchDataFromBranchId( $branchId, false );

		// create branch page
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( FormatJson::encode( $data ), $title, CONTENT_MODEL_JSON );
		$comment = CommentStoreComment::newUnsavedComment(
			wfMessage( 'createbranch-revision-comment' )->inContentLanguage()->text()
		);
		$page->newPageUpdater( $this->getUser() )
			->setContent( SlotRecord::MAIN, ContentHandler::makeContent('Branch root page', $title) )
			->setContent( 'lakat', $content )
			->saveRevision( $comment );

		return $title;
	}
}
