<?php

namespace MediaWiki\Extension\Lakat\Storage;

interface LakatStorageInterface {
	/**
	 * @param int $branchType
	 * @param string $name
	 * @param string $signature
	 * @param bool $acceptConflicts
	 * @param string $msg
	 *
	 * @return string Branch id
	 */
	public function createGenesisBranch( int $branchType, string $name, string $signature, bool $acceptConflicts, string $msg ): string;

	public function getBranchNameFromBranchId(string $branchId): string;

	//get_branch_data_from_branch_id(branch_id: str, deserialize_buckets: bool)
	//get_article_from_article_name(branch_id: str, name: str)
	//get_local_branches()

	public function branches(): array;

	//submit_content_to_twig(branch_id: str, contents: any, public_key: str, proof: str, msg: str)

	/**
	 * @param string $branchId
	 * @param string $contents
	 * contents = [
	 *     {
	 *         "data": "Hello",
	 *         "schema": DEFAULT_ATOMIC_BUCKET_SCHEMA,
	 *         "parent_id": encode_bytes_to_base64_str(bytes(0)),
	 *         "signature": encode_bytes_to_base64_str(bytes(1)),
	 *         "refs": []
	 *     },
	 *     {
	 *         "data": "World",
	 *         "schema": DEFAULT_ATOMIC_BUCKET_SCHEMA,
	 *         "parent_id": encode_bytes_to_base64_str(bytes(0)),
	 *         "signature": encode_bytes_to_base64_str(bytes(1)),
	 *         "refs": []
	 *     },
	 *     {
	 *         "data": {
	 *             "order": [
	 *                 {"id": 0, "type": BUCKET_ID_TYPE_NO_REF},
	 *                 {"id": 1, "type": BUCKET_ID_TYPE_NO_REF}],
	 *             "name": "Dummy Article Name"},
	 *         "schema": DEFAULT_MOLECULAR_BUCKET_SCHEMA,
	 *         "parent_id": encode_bytes_to_base64_str(bytes(0)),
	 *         "signature": encode_bytes_to_base64_str(bytes(1)),
	 *         "refs": []
     *     }
	 * ]
	 * @param string $publicKey
	 * @param string $proof
	 * @param string $msg
	 *
	 * @return string Branch head id
	 */
//	public function submitContentToTwig(string $branchId, string $contents, string $publicKey, string $proof, string $msg): string;
	public function submitFirst(string $branchId, string $articleName, string $content): string;
	public function submitNext(string $branchId, string $articleId, string $content): void;

	public function fetchArticle(string $branchId, string $articleId): string;

	public function findArticleIdByName(string $branchId, string $articleName): ?string;
}
