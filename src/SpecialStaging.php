<?php

namespace MediaWiki\Extension\Lakat;

use SpecialPage;

class SpecialStaging extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Staging' );
	}

	protected function getGroupName() {
		return 'lakat';
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$out = $this->getOutput();
		$out->addHTML( "<ol>" );
		for ( $i = 1; $i <= 10; $i ++ ) {
			$out->addHTML( "<li>List item</li>" );
		}
		$out->addHTML( "</ol>" );
	}
}
