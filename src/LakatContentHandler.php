<?php

namespace MediaWiki\Extension\Lakat;

use TextContentHandler;

class LakatContentHandler extends TextContentHandler {
	public function __construct() {
		parent::__construct( LakatContent::MODEL_ID );
	}

	protected function getContentClass() {
		return LakatContent::class;
	}

	public function getActionOverrides() {
		return [
			'edit' => LakatEditAction::class,
			'submit' => LakatSubmitAction::class,
			'view' => LakatViewAction::class,
		];
	}
}
