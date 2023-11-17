<?php

namespace MediaWiki\Extension\Lakat;

use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use ParserOutput;
use TextContentHandler;

class LakatContentHandler extends TextContentHandler {
	public function __construct() {
		parent::__construct( LakatContent::MODEL_ID );
	}

	protected function getContentClass() {
		return LakatContent::class;
	}

	protected function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$output ) {
		if ( $cpoParams->getGenerateHtml() ) {
			$html = htmlspecialchars( $content->getText() );
			$html = "This content was transformed for view. Here is the original content: <pre>" . $html . "</pre>";
		} else {
			$html = null;
		}

		$output->clearWrapperDivClass();
		$output->setText( $html );
	}
}
