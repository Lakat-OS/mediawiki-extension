<?php

namespace MediaWiki\Extension\Lakat\Special;

use MediaWiki\Extension\Lakat\StagingService;
use SpecialPage;

class SpecialStaging extends SpecialPage {
	private StagingService $stagingService;

	public function __construct( StagingService $stagingService ) {
		parent::__construct( 'Staging' );

		$this->stagingService = $stagingService;
	}

	protected function getGroupName() {
		return 'lakat';
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$out = $this->getOutput();

		if ( !$subPage ) {
			$out->addHTML( "Branch name not specified" );

			return;
		}

		$articles = $this->stagingService->getStagedArticles( $subPage );

		$out->addHTML( "<ul>" );
		foreach ( $articles as $article ) {
			$out->addHTML( "<li>$article</li>" );
		}
		$out->addHTML( "</ul>" );
	}
}
