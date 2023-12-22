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
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use WikiPage;

class LakatArticleMetadata {
	public static function getBranchId( string $branchName ) {
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( Title::newFromText($branchName) );
		if ( !$page->exists() ) {
			throw new Exception("Branch page doesn't exist");
		}
		$metadata = self::getPageMetadata($page);
		if (!isset($metadata->BranchId)) {
			throw new Exception('Invalid metadata: BranchId field is not set');
		}
		return $metadata->BranchId;
	}

	public static function saveArticleId( WikiPage $wikiPage, UserIdentity $user, string $articleId): void
	{
		$articleMetadataContent = ContentHandler::makeContent( FormatJson::encode(compact('articleId')), null, CONTENT_MODEL_JSON);
		$pageUpdater = $wikiPage->newPageUpdater( $user );
		$pageUpdater->setContent('lakat', $articleMetadataContent);
		$pageUpdater->saveRevision(CommentStoreComment::newUnsavedComment('Lakat: added article metadata'), EDIT_SUPPRESS_RC);
	}

	public static function hasArticleId( WikiPage $wikiPage ): bool {
		$metadata = self::getPageMetadata($wikiPage);
		return isset($metadata->articleId);
	}

	public static function getArticleId( WikiPage $wikiPage ): string {
		$metadata = self::getPageMetadata($wikiPage);
		if (!isset($metadata->articleId)) {
			throw new Exception('Article has invalid metadata: articleId field is not set');
		}
		return $metadata->articleId;
	}

	private static function getPageMetadata(WikiPage $page): object {
		$revisionRecord = $page->getRevisionRecord();
		if (!$revisionRecord->hasSlot('lakat')) {
			throw new Exception('Page has no metadata slot');
		}
		$content = $revisionRecord->getContent('lakat');
		if (! $content instanceof JsonContent || !$content->isValid()) {
			throw new Exception('Page has invalid metadata slot');
		}
		$data = $content->getData();
		if ( $data === null || !$data->isGood()) {
			throw new Exception('Page has invalid metadata');
		}
		return $data->getValue();
	}
}
