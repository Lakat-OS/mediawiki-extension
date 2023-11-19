<?php

namespace MediaWiki\Extension\Lakat;

use MediaWiki\EditPage\EditPage;

class LakatEditPage extends EditPage {
	protected function showContentForm() {
		// load actual content for edit from lakat storage
		$this->textbox1 = LakatStorage::getInstance()->loadBranch($this->getTitle()->getId());

		parent::showContentForm();
	}

	protected function importContentFormData( &$request ) {
		LakatStorage::getInstance()->saveBranch($this->getTitle()->getId(), $this->textbox1);

		// we saved content in lakat storage,
		// so now we can save nothing in mediawiki storage
		return '';
	}

}
