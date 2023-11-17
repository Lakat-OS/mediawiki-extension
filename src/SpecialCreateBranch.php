<?php

namespace MediaWiki\Extension\Lakat;

use CommentStoreComment;
use ContentHandler;
use FormSpecialPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Status;
use Title;

class SpecialCreateBranch extends FormSpecialPage
{
	private Title $createdPageTitle;

	public function __construct()
	{
		parent::__construct('CreateBranch');
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
					'createbranch-type-proper' => 'proper',
					'createbranch-type-twig' => 'twig'
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

	public function onSubmit(array $data) {
		$this->createdPageTitle = Title::newFromText($data[ 'BranchName' ]);

		$text = "Create branch request parameters:\n";
		foreach ($data as $key => $val) {
			$text .= "* $key: $val\n";
		}

		return $this->createPage($this->createdPageTitle, $text);
	}

	private function createPage(Title $title, string $wikitext = '') {
		if ( $title->isKnown() ) {
			return Status::newFatal( 'createbranch-error-already-exists' );
		}
		if ( !$title->canExist() ) {
			return Status::newFatal( 'createbranch-error-invalid-name' );
		}

		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$text = ContentHandler::makeContent( $wikitext, null, LakatContent::MODEL_ID);
		$comment = CommentStoreComment::newUnsavedComment(
			wfMessage( 'createbranch-revision-comment' )->inContentLanguage()->text()
		);

		$page->newPageUpdater( $this->getUser() )
			->setContent( SlotRecord::MAIN, $text )
			->saveRevision( $comment );

		return Status::newGood();
	}

	public function onSuccess()
	{
		$this->getOutput()->redirect( $this->createdPageTitle->getLocalURL());
	}
}
