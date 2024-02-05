<?php

namespace MediaWiki\Extension\Lakat\Special;

use FormSpecialPage;
use Html;
use MediaWiki\Extension\Lakat\StagingService;
use MediaWiki\MediaWikiServices;
use Status;

class SpecialStaging extends FormSpecialPage {
	private StagingService $stagingService;

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
		return [
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

	/**
	 * List staged articles
	 */
	protected function postHtml() {
		$html = Html::element( 'h2', [], $this->msg('staging-modified-articles')->escaped() );

		$branchName = $this->getBranchName();
		if ( !$branchName ) {
			$html .= Html::errorBox( $this->msg( 'staging-branch-not-specified' )->escaped() );

			return $html;
		}

		$articles = $this->stagingService->getStagedArticles( $branchName );
		if ( !$articles ) {
			$html .= Html::noticeBox( $this->msg( 'staging-nothing-staged' )->escaped(), '' );

			return $html;
		}

		$html .= Html::openElement( 'ul' );
		foreach ( $articles as $article ) {
			$html .= Html::element( 'li', [], $article );
		}
		$html .= Html::closeElement( 'ul' );

		return $html;
	}

	public function onSubmit( array $data ) {
		$branch = $data['branch'];
		$message = $data['message'];
		$this->stagingService->submitStaged( $this->getUser(), $branch, $message );

		return Status::newGood();
	}

	private function getBranchName(): string {
		return $this->par ?: $this->getDefaultBranch();
	}

	private function getDefaultBranch(): string {
		$user = $this->getUser();
		$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
		return $userOptionsManager->getOption( $user, 'lakat-default-branch');
	}
}
