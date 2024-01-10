<?php

namespace MediaWiki\Extension\Lakat;

use Html;
use MediaWiki\Extension\Lakat\Storage\LakatStorageStub;
use SpecialPage;
use Title;

class SpecialBranches extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Branches' );
	}

	protected function getGroupName() {
		return 'lakat';
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$branches = LakatStorageStub::getInstance()->branches();
		if ( $branches ) {
			$linkRenderer = $this->getLinkRenderer();
			$html = Html::openElement( 'ul' );
			foreach ( $branches as $branch ) {
				$link = $linkRenderer->makeKnownLink( Title::newFromText( $branch['name'] ) );
				$html .= Html::rawElement( 'li', [], $link ) . "\n";
			}
			$html .= Html::closeElement( 'ul' );
			$this->getOutput()->addHTML( $html );
		}
	}
}
