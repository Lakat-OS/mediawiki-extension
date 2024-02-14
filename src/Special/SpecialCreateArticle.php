<?php

namespace MediaWiki\Extension\Lakat\Special;

use FormSpecialPage;
use MediaWiki\Extension\Lakat\Storage\LakatStorage;
use MediaWiki\User\UserOptionsManager;
use Status;

class SpecialCreateArticle extends FormSpecialPage {
	private LakatStorage $lakatStorage;

	public function __construct( LakatStorage $lakatStorage, UserOptionsManager $userOptionsManager ) {
		parent::__construct( 'CreateArticle' );

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
		$options = [];
		foreach ($this->lakatStorage->getLocalBranches() as $branchId) {
			$branchName = $this->lakatStorage->getBranchNameFromBranchId( $branchId );
			$options[$branchName] = $branchId;
		}

		$formDescriptor = [
			'BranchId' => [
				'type' => 'select',
				'label-message' => 'createarticle-branch-name',
				'options' => $options
			],
			'ArticleName' => [
				'type' => 'text',
				'label-message' => 'createarticle-article-name',
			],
		];

		return $formDescriptor;
	}

	public function onSubmit( array $data ) {
		$branchId = $data['BranchId'];
		$articleName = $data['ArticleName'];

		$branchName = $this->lakatStorage->getBranchNameFromBranchId( $branchId );
		$this->setDefaultBranch( $branchName );

		// redirect to create page form
		$query = [
			'title' => $branchName . '/' . $articleName,
			'action' => 'edit'
		];
		$url = wfAppendQuery( wfScript(), $query );
		$this->getOutput()->redirect( $url );

		return Status::newGood();
	}

	private function setDefaultBranch( string $branchName ): void {
		$user = $this->getUser();
		$this->userOptionsManager->setOption( $user, 'lakat-default-branch', $branchName );
		$this->userOptionsManager->saveOptions( $user );
	}
}
