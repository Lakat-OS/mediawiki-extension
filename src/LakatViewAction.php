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
		$text = $this->loadBranch();
		$this->getOutput()->addWikiTextAsContent($text);

	}

	/**
	 * This is stub for lakat storage
	 */
	private function loadBranch()
	{
		$filename = $this->getPageDataFilename();
		if (!file_exists($filename)) {
			return '';
		}
		return file_get_contents($filename);
	}

	private function getPageDataFilename(): string
	{
		return MW_INSTALL_PATH . '/cache/page_' . $this->getTitle()->getId() . '.txt';
	}
}
