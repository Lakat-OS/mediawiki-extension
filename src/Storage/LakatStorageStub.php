<?php

namespace MediaWiki\Extension\Lakat\Storage;

class LakatStorageStub implements LakatStorageInterface {
	private static LakatStorageStub $instance;

	public static function getInstance(): LakatStorageStub {
		 if (!isset(self::$instance)) {
			 self::$instance = new self;
		 }
		 return self::$instance;
	}

	public function createBranch(string $name, array $options): string {
		$branchId = 'branch_' . $this->getSlug($name);

		// create directory for this branch
		$dir = $this->getBranchDir( $branchId );
		mkdir( $dir, 755);

		// store branch definition
		file_put_contents(
			$dir . '/.branch',
			json_encode(compact('name') + $options, JSON_THROW_ON_ERROR)
		);

		return $branchId;
	}

	public function branches(): array {
		$branches = [];
		foreach (glob($this->getBasePath() . '/branch_*') as $dir) {
			$id = substr($dir, strrpos($dir, '/'));
			$options = json_decode(file_get_contents($dir . '/.branch'), true, 512, JSON_THROW_ON_ERROR);
			$branches[] = compact('id') + $options;
		}
		return  $branches;
	}

	public function submitFirst( string $branchId, string $content ): string {
		$articleId = uniqid('article_');

 		$filename = $this->getArticleFile($branchId, $articleId);
		file_put_contents($filename, $content);

		return $articleId;
	}

	public function submitNext( string $branchId, string $articleId, string $content ): void {
		$filename = $this->getArticleFile($branchId, $articleId);
		file_put_contents($filename, $content);
	}

	public function fetchArticle( string $branchId, string $articleId ): string {
		return file_get_contents($this->getArticleFile($branchId, $articleId));
	}

	private function getSlug( string $name ) {
		return preg_replace( '/[^a-z0-9]/', '_', strtolower( trim( $name ) ) );
	}

	private function getBasePath(): string
	{
		return MW_INSTALL_PATH . '/cache/lakat';
	}

	private function getBranchDir(string $branchId): string
	{
		return $this->getBasePath() . '/' . $branchId;
	}

	private function getArticleFile(string $branchId, string $articleId): string
	{
		return $this->getBranchDir($branchId) . '/' . $articleId;
	}
}
