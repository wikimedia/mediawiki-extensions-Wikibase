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
			'wiki' => array( // TODO: change to "site"
				ApiBase::PARAM_TYPE => WikibaseUtils::getWikiIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'title' => 'The title of the wiki page',
			'wiki' => 'Wiki identifier',
		);
	}

	public function getDescription() {
		return array(
			'API module to obtain the Wikibase ids of one or more pages on the specified wiki.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitemid&wiki=en&title=Berlin'
				=> 'Get item id for page "Berlin" on the wiki identifierd by "en"',
			'api.php?action=wbgetitemid&wiki=en&title=Berlin|Foobar'
				=> 'Get item id for the pages "Berlin" and "Foobar" on the wiki identifierd by "en"',
		);
	}
	
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikidata#GetItemId';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
