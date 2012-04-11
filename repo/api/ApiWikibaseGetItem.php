<?php

/**
 * API module to get the data for a single Wikibase item.
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

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}


	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		// TODO: implement
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to get the data from',
			'language' => 'By default the internationalized values are returned in all available languages.
						This parameter allows filtering these down to one or more languages by providing their language codes.',
		);
	}

	public function getDescription() {
		return array(
			'API module to get the data for a single Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitem&id=42'
				=> 'Get item number 42 with default (user?) language',
            'api.php?action=wbgetitem&id=42&language=en'
				=> 'Get item number 42 with english language',
            'api.php?action=wbgetitem&id=4|2'
				=> 'Get item number 4 and 2 with default (user?) language',
            'api.php?action=wbgetitem&id=4|2&language=en'
				=> 'Get item number 4 and 2 with enlish language',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikidata#GetItem';
	}
	
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
