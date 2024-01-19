<?php

namespace MediaWiki\Extension\Lakat\Storage;

interface LakatStorageInterface {
	public function createGenesisBranch(
		int $branchType,
		string $name,
		string $signature,
		bool $acceptConflicts,
		string $msg
	): string;

	public function branches(): array;

	public function submitFirst(string $branchId, string $articleName, string $content): string;

	public function submitNext(string $branchId, string $articleId, string $content): void;

	public function fetchArticle(string $branchId, string $articleId): string;

	public function findArticleIdByName(string $branchId, string $articleName): ?string;
}
