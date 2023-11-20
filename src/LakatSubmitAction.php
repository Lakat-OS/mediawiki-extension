<?php

namespace MediaWiki\Extension\Lakat;

use MediaWiki\Session\SessionManager;

class LakatSubmitAction extends LakatEditAction {
	public function getName() {
		return 'submit';
	}

	/**
	 * @see \SubmitAction::show()
	 */
	public function show() {
		// Send a cookie so anons get talk message notifications
		SessionManager::getGlobalSession()->persist();

		parent::show();
	}
}
