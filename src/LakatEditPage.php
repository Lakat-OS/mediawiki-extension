<?php

namespace MediaWiki\Extension\Lakat;

use MediaWiki\EditPage\EditPage;

class LakatEditPage extends EditPage {
	protected function showContentForm() {
		// load actual content for edit from lakat storage
		$this->textbox1 = $this->loadBranch();

		parent::showContentForm();
	}

	protected function importContentFormData( &$request ) {
		$this->saveBranch();
		// we saved content in lakat storage,
		// so now we can save nothing in mediawiki storage
		return '';
	}

	/**
	 * This is stub for lakat storage
	 */
	private function saveBranch()
	{
		file_put_contents($this->getPageDataFilename(), $this->textbox1);
	}

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
