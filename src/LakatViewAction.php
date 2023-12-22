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
			try {
				$branchId = LakatArticleMetadata::getBranchId($title->getRootText());

				// if article doesn't exist yet then nothing to load from remote storage
				if (!$title->exists()) {
					return;
				}

				// Load page from remote storage
//				$this->getOutput()->clearHTML();
				$this->getOutput()->addWikiTextAsContent('== Content from remote storage ==');

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
