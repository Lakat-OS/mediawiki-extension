<?php

namespace MediaWiki\Extension\Lakat;

// use MediaWiki\SpecialPage\FormSpecialPage;   // this is for newer mediawiki
use FormSpecialPage;

class SpecialCreateBranch extends FormSpecialPage
{
	public function __construct()
	{
		parent::__construct('CreateBranch');
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	protected function getFormFields()
	{
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

	public function onSubmit(array $data)
	{
		return true;
	}
}
