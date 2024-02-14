<?php

namespace MediaWiki\Extension\Lakat\Special;

use Exception;
use FormSpecialPage;
use MediaWiki\Extension\Lakat\Domain\BranchType;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\User\UserOptionsManager;
use Status;
use Title;

class SpecialCreateBranch extends FormSpecialPage {
	private LakatStorage $lakatStorage;

	private UserOptionsManager $userOptionsManager;

	public function __construct( LakatStorage $lakatStorage, UserOptionsManager $userOptionsManager ) {
		parent::__construct( 'CreateBranch' );

		$this->lakatStorage = $lakatStorage;
		$this->userOptionsManager = $userOptionsManager;
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
				'required' => true,
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

		// create branch remotely
		try {
			$branchType = $data['BranchType'];
			$signature = $data['Token'];
			$acceptConflicts = $data['AllowConflicts'];
			$msg = 'Genesis submit';
			$branchId = $this->lakatStorage->createGenesisBranch( $branchType, $branchName, $signature, $acceptConflicts, $msg);
		} catch ( Exception $e ) {
			return Status::newFatal( 'createbranch-error-remote' );
		}

		// redirect to Special:FetchBranch to create branch page
		$target = Title::newFromText( 'Special:FetchBranch/' . $branchId );
		$url = $target->getFullUrlForRedirect();
		$this->getOutput()->redirect( $url );

		return Status::newGood();
	}

	private function setDefaultBranch( string $branchName ): void {
		$user = $this->getUser();
		$this->userOptionsManager->setOption( $user, 'lakat-default-branch', $branchName );
		$this->userOptionsManager->saveOptions( $user );
	}
}
