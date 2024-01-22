<?php

namespace MediaWiki\Extension\Lakat\Storage;

use Exception;
use LogicException;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerAwareTrait;
use Status;
use Wikimedia\UUID\GlobalIdGenerator;

class LakatStorageRPC implements LakatStorageInterface {
	use LoggerAwareTrait;

	private static LakatStorageRPC $instance;

	private string $url;

	private GlobalIdGenerator $globalIdGenerator;
	private HttpRequestFactory $httpRequestFactory;

	public static function getInstance() : LakatStorageRPC {
		if ( !isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->setLogger( LoggerFactory::getInstance( 'lakat-rpc' ) );

		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'lakat' );
		$this->url = $config->get( 'LakatRpcUrl' );

		$this->globalIdGenerator = MediaWikiServices::getInstance()->getGlobalIdGenerator();
		$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
	}

	public function createGenesisBranch( int $branchType, string $name, string $signature, bool $acceptConflicts, string $msg ) : string {
		$method = $this->camelToSnakeCase( __FUNCTION__ );
		$params = [
			$branchType,
			$name,
			base64_encode( $signature ),
			$acceptConflicts,
			$msg,
		];
		return $this->rpc( $method, $params );
	}

	public function getBranchNameFromBranchId( string $branchId ) : string {
		$method = $this->camelToSnakeCase( __FUNCTION__ );
		$params = [ $branchId ];
		return $this->rpc( $method, $params );
	}

	public function getLocalBranches() : array {
		$method = $this->camelToSnakeCase( __FUNCTION__ );
		$params = [];
		return $this->rpc( $method, $params );
	}

	public function submitContentToTwig( string $branchId, array $contents, string $publicKey, string $proof, string $msg ) : array {
		$method = $this->camelToSnakeCase( __FUNCTION__ );
		$params = [
			$branchId,
			$contents,
			$publicKey,
			$proof,
			$msg
		];
		return $this->rpc( $method, $params );
	}

	public function submitFirst( string $branchId, string $articleName, string $content ) : string {
		throw new LogicException( 'Not implemented' );
	}

	public function submitNext( string $branchId, string $articleId, string $content ) : void {
		throw new LogicException( 'Not implemented' );
	}

	public function getArticleFromArticleName( string $branchId, string $name ): string {
		$method = $this->camelToSnakeCase( __FUNCTION__ );
		$params = [
			$branchId,
			$name
		];
		return $this->rpc($method, $params);
	}

	private function rpc( string $method, array $params = [] ) {
		$data = [
			'jsonrpc' => '2.0',
			'id' => $this->globalIdGenerator->newRawUUIDv4(),
			'method' => $method,
		];
		if ( $params ) {
			$data['params'] = $params;
		}

		$options = [
			'method' => 'POST',
			'postData' => json_encode( $data ),
		];
		$request = $this->httpRequestFactory->create( $this->url, $options );
		$request->setHeader( 'Content-Type', 'application/json; charset=utf-8' );
		$status = $request->execute();
		if ( !$status->isOK() ) {
			$errors = $status->getErrorsByType( 'error' );
			$this->logger->error(
				serialize( Status::wrap( $status )->getMessage( false, false, 'en' ) ),
				[ 'error' => $errors, 'caller' => __METHOD__, 'content' => $request->getContent() ]
			);
			throw new Exception( 'RPC HTTP call failed: ' . var_export( $errors, 1 ) );
		}

		$jsonResponse = $request->getContent();
		$response = json_decode( $jsonResponse, true, 512, JSON_THROW_ON_ERROR );
		if ( isset( $response["error"] ) || !isset( $response['result'] ) ) {
			// Sample error response:
			//{
			//  "error": {
			//    "code": -32602,
			//    "message": "Invalid params",
			//    "data": {
			//      "type": "TypeError",
			//      "args": [
			//        "create_genesis_branch() takes 4 positional arguments but 5 were given"
			//      ],
			//      "message": "create_genesis_branch() takes 4 positional arguments but 5 were given"
			//    }
			//  },
			//  "id": "d7f316bc075f4933912566ea9bd591ef",
			//  "jsonrpc": "2.0"
			//}
			throw new Exception( 'RPC call failed: ' . $jsonResponse );
		}

		// Sample success response:
		// {"result": "AVESAmkJ", "id": "4fb833ab66314861ae081a688bb1ac18", "jsonrpc": "2.0"}
		return $response['result'];
	}

	private function camelToSnakeCase( string $str ) : string {
		return strtolower( preg_replace( '/[A-Z]/', '_$0', $str ) );
	}
}
