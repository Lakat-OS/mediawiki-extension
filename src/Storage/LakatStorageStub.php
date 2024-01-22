<?php

namespace MediaWiki\Extension\Lakat\Storage;

use Exception;

class LakatStorageStub implements LakatStorageInterface {
	private static LakatStorageStub $instance;

	public static function getInstance(): LakatStorageStub {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function createGenesisBranch(
		int $branchType,
		string $name,
		string $signature,
		bool $acceptConflicts,
		string $msg
	): string
	{
		$branchId = 'branch_' . $this->getSlug( $name );

		// create directory for this branch
		$dir = $this->getBranchDir( $branchId );
		if ( !@mkdir( $dir, 0755, true ) ) {
			throw new Exception( 'Failed to create branch directory ' . $dir );
		}

		// store branch definition
		if (
			!file_put_contents(
				$dir . '/.branch',
				json_encode( compact( 'branchType', 'name', 'signature', 'acceptConflicts', 'msg' ), JSON_THROW_ON_ERROR )
			)
		) {
			throw new Exception( 'Failed to write branch data to file ' . $dir . '/.branch' );
		}

		return $branchId;
	}

	public function getBranchNameFromBranchId(string $branchId): string
	{
		throw new \LogicException('Not implemented');
	}

	public function branches(): array {
		$branches = [];
		foreach ( glob( $this->getBasePath() . '/branch_*' ) as $dir ) {
			$id = substr( $dir, strrpos( $dir, '/' ) );
			$filename = $dir . '/.branch';
			$content = file_get_contents( $filename );
			if ( $content === false ) {
				throw new Exception( 'Failed to read branch data from file ' . $filename );
			}

			$options = json_decode( $content, true, 512, JSON_THROW_ON_ERROR );
			$branches[] = compact( 'id' ) + $options;
		}

		return $branches;
	}

	public function submitFirst( string $branchId, string $articleName, string $content ): string {
		$articleId = uniqid( 'article_' );

		$filename = $this->getArticleFile( $branchId, $articleId );
		if ( !file_put_contents( $filename, $content ) ) {
			throw new Exception( 'Failed to write article to file ' . $filename );
		}

		$this->saveArticleName2Id( $branchId, $articleName, $articleId );

		return $articleId;
	}

	public function submitContentToTwig( string $branchId, array $contents, string $publicKey, string $proof, string $msg ) : array {
		throw new \LogicException('Not implemented');
	}

	public function submitNext( string $branchId, string $articleId, string $content ): void {
		$filename = $this->getArticleFile( $branchId, $articleId );
		if ( !file_put_contents( $filename, $content ) ) {
			throw new Exception( 'Failed to write article to file ' . $filename );
		}
	}

	public function fetchArticle( string $branchId, string $articleId ): string {
		$filename = $this->getArticleFile( $branchId, $articleId );
		$content = file_get_contents( $filename );
		if ( $content === false ) {
			throw new Exception( 'Failed to read article from file ' . $filename );
		}

		return $content;
	}

	public function findArticleIdByName( string $branchId, string $articleName ): ?string {
		$filename = $this->getArticleName2IdFile( $branchId );
		$name2id = file_exists( $filename ) ? json_decode(
			file_get_contents( $filename ),
			true,
			512,
			JSON_THROW_ON_ERROR
		) : [];
		return $name2id[$articleName] ?? null;
	}

	private function saveArticleName2Id( string $branchId, string $articleName, string $articleId ): void {
		$filename = $this->getArticleName2IdFile( $branchId );
		$name2id = file_exists( $filename ) ? json_decode(
			file_get_contents( $filename ),
			true,
			512,
			JSON_THROW_ON_ERROR
		) : [];
		$name2id[$articleName] = $articleId;
		file_put_contents($filename, json_encode($name2id));
	}

	private function getSlug( string $name ) {
		return preg_replace( '/[^a-z0-9]/', '_', strtolower( trim( $name ) ) );
	}

	private function getBasePath(): string {
		return MW_INSTALL_PATH . '/cache/lakat';
	}

	private function getBranchDir( string $branchId ): string {
		return $this->getBasePath() . '/' . $branchId;
	}

	public function getArticleFromArticleName(string $branchId, string $name): string
	{
		throw new \LogicException('Not implemented');
	}

	private function getArticleFile( string $branchId, string $articleId ): string {
		return $this->getBranchDir( $branchId ) . '/' . $articleId;
	}

	private function getArticleName2IdFile( string $branchId ): string {
		return $this->getBranchDir( $branchId ) . '/.article_name2id';
	}
}
