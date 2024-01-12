<?php

namespace MediaWiki\Extension\Lakat\Storage;

interface LakatStorageInterface {
	public function createGenesisBranch(string $name, array $options): string;

	public function branches(): array;

	public function submitFirst(string $branchId, string $content): string;

	public function submitNext(string $branchId, string $articleId, string $content): void;

	public function fetchArticle(string $branchId, string $articleId): string;
}
