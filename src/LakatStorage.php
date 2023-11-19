<?php

namespace MediaWiki\Extension\Lakat;

/**
 * This is stub for lakat storage
 */
class LakatStorage {
	private static LakatStorage $instance;

	public static function getInstance(): LakatStorage {
		 if (!isset(self::$instance)) {
			 self::$instance = new self;
		 }
		 return self::$instance;
	}

	public function saveBranch(string $id, string $text)
	{
		file_put_contents($this->getPageDataFilename($id), $text);
	}

	public function loadBranch(string $id)
	{
		$filename = $this->getPageDataFilename($id);
		if (!file_exists($filename)) {
			return '';
		}
		return file_get_contents($filename);
	}

	public function getPageDataFilename(string $id): string
	{
		return MW_INSTALL_PATH . '/cache/page_' . $id . '.txt';
	}
}
