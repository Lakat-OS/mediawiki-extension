<?php

namespace MediaWiki\Extension\Lakat;

use ViewAction;

class LakatViewAction extends ViewAction {
	/**
	 * Here we replace content of the page with content fetched from lakat storage
	 */
	public function show() {
		parent::show();

		$this->getOutput()->clearHTML();

		$text = LakatStorage::getInstance()->loadBranch($this->getTitle()->getId());
		$this->getOutput()->addWikiTextAsContent($text);

	}
}
