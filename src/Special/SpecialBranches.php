<?php

namespace MediaWiki\Extension\Lakat\Special;

use Html;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use SpecialPage;
use Title;

class SpecialBranches extends SpecialPage {
	private LakatStorage $lakatStorage;

	public function __construct( LakatStorage $lakatStorage ) {
		parent::__construct( 'Branches' );

		$this->lakatStorage = $lakatStorage;
	}

	protected function getGroupName() {
		return 'lakat';
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$branchIds = $this->lakatStorage->getLocalBranches();
		if (!$branchIds) {
			return;
		}

		$html = Html::openElement( 'ul' );
		$linkRenderer = $this->getLinkRenderer();
		foreach ($branchIds as $branchId) {
			$branchName = $this->lakatStorage->getBranchNameFromBranchId($branchId);
			$title = Title::newFromText( $branchName );
			if ($title->isKnown()) {
				$link = $linkRenderer->makeKnownLink( $title );
			} else {
				$target = Title::newFromText( 'Special:FetchBranch/' . $branchId );
				$link = $linkRenderer->makeKnownLink( $target, $branchName, [ 'class' => 'new' ] );
			}
			$html .= Html::rawElement( 'li', [], $link ) . "\n";
		}
		$html .= Html::closeElement( 'ul' );
		$this->getOutput()->addHTML( $html );
	}
}
