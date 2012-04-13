<?php

/**
 * API module to associate two articles through language links in the Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseSetWikipediaTitle.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseLinkArticles extends ApiBase {

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

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function getAllowedParams() {
		return array(
			'language-from' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title-from' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language-to' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title-to' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'language-from' => 'Language code of the external page it is linked from',
			'title-from' => 'Title of the page it is linked from',
			'language-to' => 'Language code of the external page it is linked to',
			'title-to' => 'Title of the page it is linked to',
		);
	}

	public function getDescription() {
		return array(
			'API module to associate two articles on external Mediawiki pages with a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wblinkarticles&language-from=en&title-from=Norway&language-to=no&title-to=Norge'
				=> 'Set link from title "Norway" for page in English Wikipedia to "Norge" in Norwegian Wikipedia', // TODO: Still references Wikipedia
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wblinkarticles';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
