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
	public function createGenesisBranch(
		int $branchType, string $name, string $signature, bool $acceptConflicts, string $msg
	): string;

	public function getBranchNameFromBranchId( string $branchId ): string;

	/**
	 * @param string $branchId
	 * @param bool $deserializeBuckets
	 * @return array Branch data in following format:
	 * <pre>
	 * 	{
	 * 		"id": "AVESCBPj462IE3xy",
	 * 		"ns": "qjN0AAAA",
	 * 		"name": "Test branch 1706023254.6645",
	 * 		"parent_id": "",
	 * 		"stable_head": {
	 * 			"parent_submit_id": "AVESCF2LPvNdnWsBDQaqM3QAAAA=",
	 * 			"submit_msg": "submit content 1706023254.6745"
	 * 		},
	 * 		"config": {
	 * 			"accept_conflicts": true,
	 * 			"branch_type": 1
	 * 		},
	 * 		"sprouts": [],
	 * 		"sprout_selection": [],
	 * 		"name_resolution": "AVESCIUnmRZ4FpkLEAaqM3QAAAA=",
	 * 		"interaction": "AVESCDSNHSSjvABhEQaqM3QAAAA=",
	 * 		"signature": "ABAgMA==",
	 * 		"creation_ts": 1706023254,
	 * 		"submit_trace": {
	 * 			"new_buckets": [
	 * 				"AVESCKQY9RjWsyTiAQ==",
	 * 				"AVESCFV3X6zCB1LMAQ==",
	 * 				"AVESCHdqs1gzbaVPAg=="
	 * 			],
	 * 			"new_registered_names": [
	 * 				{
	 * 					"name": "Article Name 1706023254.6744",
	 * 					"id": "AVESCHdqs1gzbaVPAg=="
	 * 				}
	 * 			]
	 * 		}
	 * 	}
	 * </pre>
	 */
	public function getBranchDataFromBranchId( string $branchId, bool $deserializeBuckets ): array;

	/**
	 * @return array Array of branch ids.
	 */
	public function getLocalBranches(): array;

	/**
	 * @param string $branchId
	 * @param array $contents Array of buckets to submit. Example:
	 * <pre>
	 * [
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
	 * </pre>
	 * @param string $publicKey
	 * @param string $proof
	 * @param string $msg
	 *
	 * @return array Submit info. Example:
	 * <pre>
	 * {
	 *   "branch_id": "AVESCNlMjwzVG7QB",
	 *   "bucket_refs": [
	 *     "AVESCKfMU7ZqHW5OAQ==",
	 *     "AVESCAEje3RqTDDCAQ==",
	 *     "AVESCJG6DxbpGP2rAg==",
	 *     "AVESCMNhd4mD1duZAQ==",
	 *     "AVESCE3iK7qOVkGIAg=="
	 *   ],
	 *   "registered_names": [
	 *     {
	 *       "name": "Dummy Article Name",
	 *       "id": "AVESCJG6DxbpGP2rAg=="
	 *     },
	 *     {
	 *       "name": "Another Article Name",
	 *       "id": "AVESCE3iK7qOVkGIAg=="
	 *     }
	 *   ],
	 *   "submit_trace_id": "AVESCItDYk9OywOwDgY0VtEAAAA=",
	 *   "submit_id": "AVESCJMDCAnR63TBDQY0VtEAAAA=",
	 *   "branch_state_id": "AVESCNKdydLjwoCv"
	 * }
	 * </pre>
	 */
	public function submitContentToTwig(
		string $branchId, array $contents, string $publicKey, string $proof, string $msg
	): array;

	/**
	 * @param string $branchId
	 * @param string $name
	 *
	 * @return string Article content as all article buckets concatenated.
	 */
	public function getArticleFromArticleName( string $branchId, string $name ): string;
}
