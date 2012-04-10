<?php

/**
 * API module get the data for a single Wikibase item.
 *
 * @since 0.1
 *
 * @file ApiWikibaseGetItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseGetItem extends ApiBase {


	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		// TODO: implement
	}

	public function needsToken() {
		return true;
	}

	public function mustBePosted() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => array(),
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to get the data from',
			'language' => 'Language in which labels should be returned',
		);
	}

	public function getDescription() {
		return array(
			'API module get the data for a single Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=deleteeducation&ids=42&type=course',
			'api.php?action=deleteeducation&ids=4|2&type=org',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
