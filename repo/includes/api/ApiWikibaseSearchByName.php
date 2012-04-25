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
 * @author John Erling Blad < jeblad@gmail.com >
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
		// TODO: implement this
		}

	public function getAllowedParams() {
		return array(
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'fragment' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'hints' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'language' => 'Search within this language.',
			'fragment' => 'The ?? to search for',
			'hints' => 'Hints of some kind',
		);
	}

	public function getDescription() {
		return array(
			'API module to search for ?? .'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsearchbyname&fragment=foobar&language=en'
			=> 'Search for "foobar" in english language',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsearchbyname';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
