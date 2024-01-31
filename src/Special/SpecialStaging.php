<?php

namespace MediaWiki\Extension\Lakat\Special;

use FormSpecialPage;
use Html;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\MediaWikiServices;
use Status;

class SpecialStaging extends FormSpecialPage {
	private StagingService $stagingService;

	private string $branchName;

	private array $stagedArticles;

	public function __construct( StagingService $stagingService ) {
		parent::__construct( 'Staging' );

		$this->stagingService = $stagingService;
	}

	protected function getGroupName() {
		return 'lakat';
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	protected function getSubpageField() {
		return 'branch';
	}

	protected function getFormFields() {
		$branchName = $this->getBranchName();
		if ( !$branchName ) {
			return [];
		}

		$articles = $this->getStagedArticles( $branchName );
		$options = array_combine( $articles, $articles );

		return [
			'articles' => [
				'type' => 'multiselect',
				'label-message' => 'staging-modified-articles',
				'options' => $options,
				'default' => $articles,
			],
			'branch' => [
				'type' => 'text',
				'label-message' => 'staging-branch',
				'default' => $this->getBranchName(),
				'readonly' => true,
			],
			'message' => [
				'type' => 'text',
				'label-message' => 'staging-message',
			],
		];
	}

	protected function preHtml() {
		$branchName = $this->getBranchName();
		if ( !$branchName ) {
			return Html::errorBox( $this->msg( 'staging-branch-not-specified' )->escaped() );
		}

		$articles = $this->getStagedArticles( $branchName );
		if ( !$articles ) {
			return Html::noticeBox( $this->msg( 'staging-nothing-staged' )->escaped(), '' );
		}

		return '';
	}

	public function onSubmit( array $data ) {
		$articles = $data['articles'];
		$branch = $data['branch'];
		$message = $data['message'];
		$this->stagingService->submitStaged( $this->getUser(), $branch, $articles, $message );

		return Status::newGood();
	}

	private function getBranchName(): string {
		if (!isset($this->branchName)) {
			$this->branchName = $this->par ?: $this->getDefaultBranch();
		}
		return $this->branchName;
	}

	private function getDefaultBranch(): string {
		$user = $this->getUser();
		$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
		return $userOptionsManager->getOption( $user, 'lakat-default-branch');
	}

	public function getStagedArticles( string $branchName ): array {
		if (!isset($this->stagedArticles)) {
			$this->stagedArticles = $this->stagingService->getStagedArticles( $branchName );
		}
		return $this->stagedArticles;
	}
}
