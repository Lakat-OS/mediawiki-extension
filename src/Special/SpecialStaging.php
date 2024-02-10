<?php

namespace MediaWiki\Extension\Lakat\Special;

use FormSpecialPage;
use Html;
use HTMLForm;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\Title\Title;
use MediaWiki\User\UserOptionsManager;
use Status;

class SpecialStaging extends FormSpecialPage {
	private StagingService $stagingService;

	private UserOptionsManager $userOptionsManager;

	private string $branchName;

	private array $stagedArticles;

	public function __construct( StagingService $stagingService, UserOptionsManager $userOptionsManager) {
		parent::__construct( 'Staging' );

		$this->stagingService = $stagingService;
		$this->userOptionsManager = $userOptionsManager;
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
		$links = array_map( function ( $article ) use ( $branchName ) {
			return HTML::element( 'a',
				[ 'href' => Title::newFromText( "$branchName/$article" )->getLocalURL() ],
				$article );
		}, $articles );
		$options = array_combine( $links, $articles );

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

	protected function alterForm( HTMLForm $form ) {
		$form->addButton( [
			'name' => 'reset',
			'value' => 'reset',
			'label-message' => 'staging-label-reset',
			'flags' => [ 'destructive' ],
		] );
	}

	public function onSubmit( array $data ) {
		$articles = $data['articles'];
		$branch = $data['branch'];
		$message = $data['message'];

		$shouldReset = (bool)$this->getRequest()->getVal( 'reset' );
		if ( $shouldReset ) {
			try {
				$this->stagingService->resetStaged( $this->getUser(), $branch, $articles );
			} catch ( \Exception $e ) {
				return Status::newFatal( 'Failed to reset articles: ' . $e->getMessage() );
			}
		} else {
			try {
				$this->stagingService->submitStaged( $this->getUser(), $branch, $articles, $message );
			} catch ( \Exception $e ) {
				return Status::newFatal( 'Failed to submit articles: ' . $e->getMessage() );
			}
		}

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
		return $this->userOptionsManager->getOption( $user, 'lakat-default-branch');
	}

	public function getStagedArticles( string $branchName ): array {
		if (!isset($this->stagedArticles)) {
			$this->stagedArticles = $this->stagingService->getStagedArticles( $branchName );
		}
		return $this->stagedArticles;
	}
}
