<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use FormatJson;
use JsonContent;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use WikiPage;

class LakatArticleMetadata {
	public static function getBranchId( string $branchName ) {
		// Retrieve branch page to extract branch id from it
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( Title::newFromText($branchName) );
		if ( !$page->exists() ) {
			throw new Exception(sprintf( "Branch '''%s''' page doesn't exist", $branchName ));
		}

		// Parse branch id from branch page
		$content = $page->getContent();
		$data = $content instanceof JsonContent ? $content->getData() : null;
		if ( $data === null || !$data->isGood()) {
			throw new Exception(sprintf( "Branch '''%s''' page is invalid", $branchName ));
		}
		$value = $data->getValue();
		if (!isset($value->BranchId)) {
			throw new Exception(sprintf( "Branch '''%s''' has invalid page: BranchId field not set", $branchName ));
		}

		return $data->getValue()->BranchId;
	}

	public static function saveArticleId( WikiPage $wikiPage, Authority|UserIdentity $user, string $articleId): void
	{
		$articleMetadataContent = ContentHandler::makeContent( FormatJson::encode(compact('articleId')), null, CONTENT_MODEL_JSON);
		$pageUpdater = $wikiPage->newPageUpdater( $user );
		$pageUpdater->setContent('lakat', $articleMetadataContent);
		$pageUpdater->saveRevision(CommentStoreComment::newUnsavedComment('Lakat: added article metadata'), EDIT_SUPPRESS_RC);
	}

	public static function getArticleId( WikiPage $wikiPage ): string {
		$revisionRecord = $wikiPage->getRevisionRecord();
		if (!$revisionRecord->hasSlot('lakat')) {
			throw new Exception(sprintf( "Article '''%s''' has no metadata slot", $wikiPage->getTitle()->getText() ));
		}
		$content = $revisionRecord->getContent('lakat');
		if (! $content instanceof JsonContent || !$content->isValid()) {
			throw new Exception(sprintf( "Article '''%s''' has invalid metadata slot", $wikiPage->getTitle()->getText() ));
		}
		$articleMetadata = $content->getData()->getValue();
		if (!isset($articleMetadata->articleId)) {
			throw new Exception(sprintf( "Article '''%s''' has invalid metadata: articleId field not set", $wikiPage->getTitle()->getText() ));
		}
		return $articleMetadata->articleId;
	}
}
