<?php

namespace MediaWiki\Extension\Lakat\Special;

use ErrorPageError;
use FormSpecialPage;
use Html;
use HTMLForm;
use MediaWiki\Extension\Lakat\StagedArticle;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\Title\Title;
use MediaWiki\User\UserOptionsManager;
use Status;
use User;

class SpecialStaging extends FormSpecialPage {
	private StagingService $stagingService;

	private UserOptionsManager $userOptionsManager;

	private ?string $branchName;

	/**
	 * @var StagedArticle[]
	 */
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

		$stagedArticles = $this->getStagedArticles( $branchName );

		// prepare options for multiselect
		$options = [];
		foreach ($stagedArticles as $stagedArticle) {
			$articleName = $stagedArticle->articleName;

			// link to page
			$pageUrl = Title::newFromText( "$branchName/$articleName" )->getLocalURL();
			$pageLink = HTML::element( 'a', [ 'href' => $pageUrl ], $articleName );

			// link to diff
			if ($stagedArticle->revId) {
				$diffParams = [
					'diff' => 'cur',
					'oldid' => $stagedArticle->revId,
					'direction' => 'prev'
				];
				$diffUrl = wfAppendQuery( wfScript(), $diffParams );
				$diffLink = HTML::element( 'a', [ 'href' => $diffUrl ], 'diff' );
			} else {
				// diff not needed for a new article
				$diffLink = 'new';
			}

			$options["$pageLink&nbsp;|&nbsp;$diffLink"] = $articleName;
		}

		return [
			'articles' => [
				'type' => 'multiselect',
				'label-message' => 'staging-modified-articles',
				'options' => $options,
				'default' => array_map( fn( $stagedArticle ) => $stagedArticle->articleName, $stagedArticles ),
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

	protected function checkExecutePermissions( User $user ) {
		$this->requireNamedUser();

		if (!$this->getBranchName()) {
			throw new ErrorPageError( 'staging-no-branch-title', 'staging-no-branch-msg' );
		}
	}

	private function getBranchName(): ?string {
		if (!isset($this->branchName)) {
			$this->branchName = $this->par ?: $this->getDefaultBranch();
		}
		return $this->branchName;
	}

	private function getDefaultBranch(): ?string {
		$user = $this->getUser();
		return $this->userOptionsManager->getOption( $user, 'lakat-default-branch');
	}

	/**
	 * @param string $branchName
	 * @return StagedArticle[]
	 */
	public function getStagedArticles( string $branchName ): array {
		if (!isset($this->stagedArticles)) {
			$this->stagedArticles = $this->stagingService->getStagedArticles( $branchName );
		}
		return $this->stagedArticles;
	}
}
