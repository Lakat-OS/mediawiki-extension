<?php

namespace MediaWiki\Extension\Lakat\Domain;

abstract class Bucket {
	abstract public static function getSchema() : int;
}
