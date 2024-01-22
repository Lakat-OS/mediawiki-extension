<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use FormatJson;
use JsonContent;
use MediaWiki\MediaWikiServices;
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
		if (!isset($metadata['BranchId'])) {
			throw new Exception('Invalid metadata: BranchId field is not set');
		}
		return $metadata['BranchId'];
	}

	public static function save( WikiPage $wikiPage, UserIdentity $user, array $data): void
	{
		$pageUpdater = $wikiPage->newPageUpdater( $user );

		$content = ContentHandler::makeContent( FormatJson::encode($data), null, CONTENT_MODEL_JSON);
		$pageUpdater->setContent('lakat', $content);

		$summary = CommentStoreComment::newUnsavedComment( 'Lakat: updated article metadata' );
		$flags = EDIT_INTERNAL | EDIT_SUPPRESS_RC;
		$pageUpdater->saveRevision( $summary, $flags );
	}

	public static function load( WikiPage $wikiPage ): array {
		return self::getPageMetadata($wikiPage);
	}

	private static function getPageMetadata( WikiPage $page ): array {
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
		return (array)$data->getValue();
	}
}
