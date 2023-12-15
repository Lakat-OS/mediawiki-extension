<?php

namespace MediaWiki\Extension\Lakat;

use Html;
use MediaWiki\Extension\Lakat\Storage\LakatStorageStub;
use SpecialPage;

class SpecialBranches extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Branches' );
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$branches = LakatStorageStub::getInstance()->branches();
		if ( $branches ) {
			$linkRenderer = $this->getLinkRenderer();
			$html = Html::openElement( 'ul' );
			foreach ( $branches as $branch ) {
				$html .= Html::rawElement( 'li', [], $linkRenderer->makeKnownLink( $branch['name'] ) ) . "\n";
			}
			$html .= Html::closeElement( 'ul' );
			$this->getOutput()->addHTML( $html );
		}
	}

	protected function getGroupName() {
		return 'lakat';
	}
}
