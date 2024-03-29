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

use Article;
use DatabaseUpdater;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRoleRegistry;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use User;

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
				'title' => $sktemplate->msg( 'lakat-switchbranch-tooltip' )->text(),
			],
			'createbranch' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-create-branch' )->text(),
				'href' => Title::newFromText('Special:CreateBranch')->getLinkURL(),
				'title' => $sktemplate->msg( 'lakat-create-branch-tooltip' )->text(),
			],
			'createarticle' => [
				'class' => false,
				'text' => $sktemplate->msg( 'lakat-create-article' )->text(),
				'href' => Title::newFromText('Special:CreateArticle')->getLinkURL(),
				'title' => $sktemplate->msg( 'lakat-create-article-tooltip' )->text(),
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
			],
			'staging' => [
				'class' => false,
				'text' => $sktemplate->msg( 'staging' )->text(),
				'href' => Title::newFromText('Special:Staging')->getLinkURL(),
				'title' => $sktemplate->msg( 'staging-summary' )->text(),
			],
		];
	}

	/**
	 * This hook resolves branch for a new article and redirects to Branch/Title edit page
	 */
	public static function onCustomEditor( Article $article, User $user ): bool {
		// ignore existing articles
		if ($article->getTitle()->exists()) {
			return true;
		}

		// redirect new article to current branch if not yet
		$titleText = $article->getTitle()->getText();
		$branchName = self::getCurrentBranch();
		if ( !str_starts_with( $titleText, $branchName . '/') ) {
			$redirectTo = 'Special:EditPage/' . $branchName . '/' . $titleText;
			$article->getContext()->getOutput()->redirect( Title::newFromText($redirectTo)->getLocalURL() );
			return false;
		}

		// new article already in branch, do nothing
		return true;
	}

	public static function getCurrentBranch(): string {
		// TODO: replace this stub
		return 'BranchX';
	}

	/**
	 * Here we add new user preferences
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		// Default Lakat branch for the user
		$preferences['lakat-default-branch'] = [
			'section' => 'lakat/options',
			'type' => 'text',
			'label-message' => 'lakat-default-branch',
		];
	}

	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ): void {
		// skip internal edits to avoid infinite loop when saving metadata
		if ($flags & EDIT_INTERNAL) {
			return;
		}

		// article is a subpage of a branch page
		$title = $wikiPage->getTitle();
		if ($title->isSubpage()) {
			$branchName = $title->getRootText();

			// ensure branch $branchName exists
			LakatArticleMetadata::getBranchId( $branchName );

			$articleName = $title->getSubpageText();

			// stage article
			$revisionStore = MediaWikiServices::getInstance()->getRevisionStore();
			$prevRevId = $revisionStore->getPreviousRevision( $revisionRecord )?->getId();
			LakatServices::getStagingService()->stage( $branchName, $articleName, $prevRevId );
		}
	}

	public function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$dbType = $updater->getDB()->getType();
		$dir = __DIR__ . "/../sql";

		$updater->addExtensionTable( StagingService::TABLE, "$dir/$dbType/tables-generated.sql" );
	}
}
