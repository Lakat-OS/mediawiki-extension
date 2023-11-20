<?php

namespace MediaWiki\Extension\Lakat;

use EditAction;
use MediaWiki\MainConfigNames;

class LakatEditAction extends EditAction {
	/**
	 * This method is the same as EditAction::show() except EditPage is replaced with customized LakatEditPage.
	 */
	public function show() {
		$this->useTransactionalTimeLimit();

		$out = $this->getOutput();
		$out->setRobotPolicy( 'noindex,nofollow' );

		$out->disableClientCache();

		if ( $this->getContext()->getConfig()->get( MainConfigNames::UseMediaWikiUIEverywhere ) ) {
			$out->addModuleStyles( [
				'mediawiki.ui.input',
				'mediawiki.ui.checkbox',
			] );
		}

		$article = $this->getArticle();
		if ( $this->getHookRunner()->onCustomEditor( $article, $this->getUser() ) ) {
			$editor = new LakatEditPage( $article );	// use custom edit page form
			$editor->setContextTitle( $this->getTitle() );
			$editor->edit();
		}
	}
}
