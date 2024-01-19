<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use Exception;
use FormatJson;
use FormSpecialPage;
use LogicException;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\Storage\LakatStorageRPC;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Status;
use Title;

class SpecialCreateBranch extends FormSpecialPage {
	private Title $branchPageTitle;

	public function __construct() {
		parent::__construct( 'CreateBranch' );
	}

	protected function getGroupName() {
		return 'lakat';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	protected function getFormFields() {
		$formDescriptor = [
			'BranchName' => [
				'type' => 'text',
				'label-message' => 'createbranch-name',
			],
			'Token' => [
				'type' => 'text',
				'label-message' => 'createbranch-token',
			],
			'BranchType' => [
				'type' => 'radio',
				'flatlist' => 1,
				'options-messages' => [
					'createbranch-type-proper' => BranchType::PROPER,
					'createbranch-type-twig' => BranchType::TWIG
				],
				'default' => 'proper',
			],
			'AllowConflicts' => [
				'type' => 'check',
				'label-message' => 'createbranch-allow-conflicts',
				'value' => 0,
			],
		];

		return $formDescriptor;
	}

	public function onSubmit( array $data ) {
		$branchName = $data['BranchName'];

		$this->setDefaultBranch( $branchName );

		$title = Title::newFromText( $branchName );
		if ( $title->isKnown() ) {
			return Status::newFatal( 'createbranch-error-already-exists' );
		}
		if ( !$title->canExist() ) {
			return Status::newFatal( 'createbranch-error-invalid-name' );
		}
		$this->branchPageTitle = $title;

		// create branch remotely
		try {
			$branchType = $data['BranchType'];
			$signature = $data['Token'];
			$acceptConflicts = $data['AllowConflicts'];
			$msg = 'Genesis submit';
			$branchId = LakatStorageRPC::getInstance()->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg);
		} catch ( Exception $e ) {
			return Status::newFatal( 'createbranch-error-remote' );
		}

		// create branch root page
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );

		$json = FormatJson::encode( [ 'BranchId' => $branchId ] + $data );
		$content = ContentHandler::makeContent( $json, $title, CONTENT_MODEL_JSON );
		if ( !$content->isValid() ) {
			throw new LogicException( 'Json parsing failed' );
		}

		$comment = CommentStoreComment::newUnsavedComment(
			wfMessage( 'createbranch-revision-comment' )->inContentLanguage()->text()
		);

		$page->newPageUpdater( $this->getUser() )
			->setContent( SlotRecord::MAIN, ContentHandler::makeContent('Branch root page', $title) )
			->setContent( 'lakat', $content )
			->saveRevision( $comment );

		return Status::newGood();
	}

	private function setDefaultBranch( string $branchName ): void {
		$user = $this->getUser();
		$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
		$userOptionsManager->setOption( $user, 'lakat-default-branch', $branchName );
		$userOptionsManager->saveOptions( $user );
	}

	public function onSuccess() {
		$this->getOutput()->redirect( $this->branchPageTitle->getLocalURL() );
	}
}
