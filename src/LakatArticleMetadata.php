<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use FormatJson;
use JsonContent;
use MediaWiki\Language\RawMessage;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\UserIdentity;
use WikiPage;

class LakatArticleMetadata {
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
