<?php

/**
 * API module to obtain the Wikibase ids of one or more pages on a Wikipedia.
 *
 * @since 0.1
 *
 * @file ApiWikibaseGetItemId.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseGetItemId extends ApiBase {


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
				ApiBase::PARAM_ISMULTI => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => array(),
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'title' => 'The title of the Wikipedia page',
			'language' => 'Language code of the Wikipedia the title belongs to',
		);
	}

	public function getDescription() {
		return array(
			'API module to obtain the Wikibase ids of one or more pages on a Wikipedia.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitemid&title=Wikimedia&language=en',
			'api.php?action=wbgetitem&title=Wikimedia|Wikipedia&language=en',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
