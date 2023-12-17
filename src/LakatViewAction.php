<?php

namespace MediaWiki\Extension\Lakat;

use JsonContent;
use LogicException;
use MediaWiki\Extension\Lakat\Storage\LakatStorageStub;
use MediaWiki\Language\RawMessage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use ViewAction;

class LakatViewAction extends ViewAction {
	/**
	 * Here we replace content of the page with content fetched from lakat storage
	 */
	public function show() {
		parent::show();

		// Article is a subpage of a branch page
		$title = $this->getTitle();
		if ( $title->isSubpage() ) {
			// Retrieve branch page to extract branch id from it
			$branchTitle = $title->getBaseTitle();
			$branchPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $branchTitle );
 				if ( !$branchPage->exists() ) {
				$this->getOutput()->showErrorPage(
					new RawMessage("Branch error"),
					new RawMessage(sprintf( "Branch '''%s''' doesn't exist locally", $branchTitle->getText() ))
				);
				return;
			}

			// Parse branch id from branch page
			$branchContent = $branchPage->getContent();
			$branchData = $branchContent instanceof JsonContent ? $branchContent->getData() : null;
			if ( $branchData === null || !$branchData->isGood() ) {
				$this->getOutput()->showFatalError(
					new RawMessage(sprintf( "Branch page '''%s''' is invalid", $branchTitle->getText() ))
				);
				return;
			}
			$branchId = $branchData->getValue()->BranchId;

			// if article doesn't exist then allow user to create it
			if (!$title->exists()) {
				return;
			}

			// ignore content from local storage
//			$this->getOutput()->clearHTML();

			// Load page from remote storage
			// Get articleId from lakat slot
			$revisionRecord = $this->getWikiPage()->getRevisionRecord();
			if (!$revisionRecord->hasSlot('lakat')) {
				$this->getOutput()->showFatalError(
					new RawMessage(sprintf( "Article '''%s''' have no metadata slot", $title->getText() ))
				);
				return;
			}
			$slotRecord = $revisionRecord->getSlot('lakat');
			$articleMetadataContent = $slotRecord->getContent();
			if (! $articleMetadataContent instanceof JsonContent || !$articleMetadataContent->isValid()) {
				$this->getOutput()->showFatalError(
					new RawMessage(sprintf( "Article '''%s''' has invalid metadata slot", $title->getText() ))
				);
				return;
			}
			$articleMetadata = $articleMetadataContent->getData()->getValue();
			if (!isset($articleMetadata->articleId)) {
				$this->getOutput()->showFatalError(
					new RawMessage(sprintf( "Article '''%s''' has invalid metadata: articleId field not set", $title->getText() ))
				);
			}
			$articleId = $articleMetadata->articleId;

			// output content from remote storage
			$this->getOutput()->addWikiTextAsContent('== Content from remote storage ==');
			$text = LakatStorageStub::getInstance()->fetchArticle($branchId, $articleId);
			$this->getOutput()->addWikiTextAsContent($text);
		}
	}
}
