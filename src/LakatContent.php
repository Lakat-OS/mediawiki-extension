<?php

namespace MediaWiki\Extension\Lakat;

use TextContent;

class LakatContent extends TextContent {

	public const MODEL_ID = 'lakat';

	public function __construct( $text ) {
		parent::__construct( $text, self::MODEL_ID );
	}
}
