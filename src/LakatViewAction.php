<?php

namespace MediaWiki\Extension\Lakat;

use Exception;
use JsonContent;
use LogicException;
use MediaWiki\Extension\Lakat\Storage\LakatStorageStub;
use MediaWiki\Language\RawMessage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
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

			// if article doesn't exist yet then nothing to load from remote storage
			if (!$title->exists()) {
				return;
			}

			// Load page from remote storage
//			$this->getOutput()->clearHTML();
			$this->getOutput()->addWikiTextAsContent('== Content from remote storage ==');
			try {
				$articleId = LakatArticleMetadata::getArticleId( $this->getWikiPage() );
				$text = LakatStorageStub::getInstance()->fetchArticle($branchId, $articleId);
				$this->getOutput()->addWikiTextAsContent($text);
			} catch ( Exception $e) {
				$this->getOutput()->showFatalError( new RawMessage($e->getMessage()) );
				return;
			}

		}
	}
}
