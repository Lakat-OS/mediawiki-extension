<?php

namespace MediaWiki\Extension\Lakat;

use Exception;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
use MediaWiki\Language\RawMessage;
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
				$branchId = LakatArticleMetadata::getBranchId( $title->getRootText() );

				// if article doesn't exist locally, we need to check remote storage
				if (!$title->exists()) {
					return;	// ignore for now
//					$articleId = LakatStorageStub::getInstance()->findArticleIdByName($branchId, $title->getSubpageText());
//					if (!$articleId) {
//						// article with this name doesn't exist remotely, skip to usual mediawiki processing
//						return;
//					}
//
//					// article exists remotely, creating local page for the article
//					$text = LakatStorageStub::getInstance()->fetchArticle($branchId, $articleId);
//
//					$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
//
//					$json = FormatJson::encode( compact('articleId') );
//					$content = ContentHandler::makeContent( $json, $title, CONTENT_MODEL_JSON );
//					if ( !$content->isValid() ) {
//						throw new LogicException( 'Json parsing failed' );
//					}
//
//					$comment = CommentStoreComment::newUnsavedComment(
//						wfMessage( 'fetcharticle-revision-comment' )->inContentLanguage()->text()
//					);
//
//					$page->newPageUpdater( $this->getUser() )
//						->setContent( SlotRecord::MAIN, ContentHandler::makeContent($text, $title) )
//						->setContent( 'lakat', $content )
//						->saveRevision( $comment );
//
//					// redirect to the newly created page
//					$this->getOutput()->redirect($title->getLocalURL());
//					return;
				}

				// Load page from remote storage
//				$this->getOutput()->clearHTML();

//				$this->getOutput()->addWikiTextAsContent('== Content from remote storage ==');
//
//				$articleName = $title->getSubpageText();
//				$text = LakatStorageRPC::getInstance()->getArticleFromArticleName( $branchId, $articleName );
//
//				$this->getOutput()->addWikiTextAsContent($text);
			} catch ( Exception $e) {
				$this->getOutput()->showFatalError( new RawMessage($e->getMessage()) );
				return;
			}

		}
	}
}
