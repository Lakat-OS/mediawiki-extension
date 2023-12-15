<?php

namespace MediaWiki\Extension\Lakat\Storage;

interface LakatStorageInterface {
	public function createBranch(string $name, array $options): string;

	public function submitFirst(string $branchId, string $content): string;

	public function submitNext(string $branchId, string $articleId, string $content): void;

	public function fetchArticle(string $branchId, string $articleId): string;
}
