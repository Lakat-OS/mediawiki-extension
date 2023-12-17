<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use JsonContent;
use LogicException;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\Lakat\Storage\LakatStorageStub;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Revision\SlotRoleRegistry;
use MediaWiki\Settings\Source\Format\JsonFormat;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use SkinTemplate;
use Status;
use WikiPage;

class Hooks implements
	MediaWikiServicesHook,
	BeforePageDisplayHook,
	SkinTemplateNavigation__UniversalHook,
	PageSaveCompleteHook
{
	public function onMediaWikiServices( $services ): void {
		$services->addServiceManipulator(
			'SlotRoleRegistry',
			function (
				SlotRoleRegistry $registry
			) {
				$registry->defineRoleWithModel( 'lakat', CONTENT_MODEL_JSON );
			});
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$config = $out->getConfig();
		if ( $config->get( 'LakatVandalizeEachPage' ) ) {
			$out->addModules( 'oojs-ui-core' );
			$out->addHTML( \Html::element( 'p', [], 'Lakat was here' ) );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {

		global $wgSitename, $wgServer;

		$currentTitle = $sktemplate->getTitle();

		$links['actions']['social'] = [
			'class' => false,
			'text' => $sktemplate->msg( 'lakat-social' )->text(),
			'href' => 'https://google.com',
			'title' => $sktemplate->msg( 'lakat-social-tooltip' )->text(),
		];

		$links['views']['review'] = [
			'class' => false,
			'text' => $sktemplate->msg( 'lakat-review' )->text(),
			'href' => 'https://google.com',
			'title' => $sktemplate->msg( 'lakat-review-tooltip' )->text(),
		];
		$links['views']['diffs'] = [
			'class' => false,
			'text' => $sktemplate->msg( 'lakat-diffs' )->text(),
			'href' => 'https://example.com',
			'title' => $sktemplate->msg( 'lakat-diffs-tooltip' )->text(),
		];

		$urlParts = parse_url($wgServer);
		$port = '8280';
		if (isset($urlParts['port'])) {
			if ($urlParts['port']=='8280') {
				$port = '8281';
			} else {
				$port = '8280';
			}
		}

		$fullUrl = "http://localhost:" . $port . "/index.php/" . $currentTitle->getText();

		$links['namespaces'] = [
			'switchbranch' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-switchbranch' )->text(),
				'href' => $fullUrl,
				'title' => $sktemplate->msg( 'lakat-switchbranch-tooltip' )->text()
			],
			'createbranch' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-create-branch' )->text(),
				'href' => Title::newFromText('Special:CreateBranch')->getLinkURL(),
				'title' => $sktemplate->msg( 'lakat-create-branch-tooltip' )->text(),
			],
			'branchconfig' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-branch-config' )->text(),
				'href' => 'https://another-example.com',
				'title' => $sktemplate->msg( 'lakat-branch-config-tooltip' )->text(),
			],
			'tokens' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-token' )->text(),
				'href' => 'https://another-example.com',
				'title' => $sktemplate->msg( 'lakat-token-tooltip' )->text(),
			]
		];
	}

	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ): void {
		// Article is a subpage of a branch page
		$title = $wikiPage->getTitle();
		if ($title->isSubpage()) {
			// Retrieve branch page to extract branch id from it
			$branchTitle = $title->getBaseTitle();
			$branchPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $branchTitle );
			if ( !$branchPage->exists() ) {
				throw new Exception(sprintf( "Branch '''%s''' doesn't exist locally", $branchTitle->getText() ));
			}

			// Parse branch id from branch page
			$branchContent = $branchPage->getContent();
			$branchData = $branchContent instanceof JsonContent ? $branchContent->getData() : null;
			if ( $branchData === null || !$branchData->isGood() ) {
				throw new Exception(sprintf( "Branch page '''%s''' is invalid", $branchTitle->getText() ));
			}
			$branchId = $branchData->getValue()->BranchId;

			// Save page in remote storage
			$blob = $revisionRecord->getContent(SlotRecord::MAIN)->serialize();
			if ($editResult->isNew()) {
				$articleId = LakatStorageStub::getInstance()->submitFirst($branchId, $blob);

				// save articleId in lakat slot
				$articleMetadataContent = ContentHandler::makeContent(\FormatJson::encode(compact('articleId')), null, CONTENT_MODEL_JSON);
				$pageUpdater = $wikiPage->newPageUpdater( $user );
				$pageUpdater->setContent('lakat', $articleMetadataContent);
				$pageUpdater->saveRevision(CommentStoreComment::newUnsavedComment('Lakat: added article metadata'), EDIT_SUPPRESS_RC);
			} else {
				// get articleId from lakat slot
				if (!$revisionRecord->hasSlot('lakat')) {
					throw new Exception(sprintf( "Article '''%s''' have no metadata slot", $title->getText() ));
				}
				$slotRecord = $revisionRecord->getSlot('lakat');
				$articleMetadataContent = $slotRecord->getContent();
				if (! $articleMetadataContent instanceof JsonContent || !$articleMetadataContent->isValid()) {
					throw new Exception(sprintf( "Article '''%s''' has invalid metadata slot", $title->getText() ));
				}
				$articleMetadata = $articleMetadataContent->getData()->getValue();
				if (!isset($articleMetadata->articleId)) {
					throw new Exception(sprintf( "Article '''%s''' has invalid metadata: articleId field not set", $title->getText() ));
				}
				$articleId = $articleMetadata->articleId;

				LakatStorageStub::getInstance()->submitNext($branchId, $articleId, $blob);
			}
		}
	}
}
