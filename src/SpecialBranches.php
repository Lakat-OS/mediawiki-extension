<?php

namespace MediaWiki\Extension\Lakat;

use Html;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
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

		$branchIds = LakatStorageRPC::getInstance()->getLocalBranches();
		if (!$branchIds) {
			return;
		}

		$html = Html::openElement( 'ul' );
		$linkRenderer = $this->getLinkRenderer();
		foreach ($branchIds as $branchId) {
			$branchName = LakatStorageRPC::getInstance()->getBranchNameFromBranchId($branchId);
			$link = $linkRenderer->makeKnownLink( Title::newFromText( $branchName ) );
			$html .= Html::rawElement( 'li', [], $link ) . "\n";
		}
		$html .= Html::closeElement( 'ul' );
		$this->getOutput()->addHTML( $html );
	}
}
