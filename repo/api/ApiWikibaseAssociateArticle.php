<?php

/**
 * API module to associate an article on a wiki with a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseAssociateArticle.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseAssociateArticle extends ApiBase {

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
		return true;
	}

	public function mustBePosted() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
			),
			'wiki' => array(
				ApiBase::PARAM_TYPE => array(), // TODO: wiki list
				ApiBase::PARAM_REQUIRED => true,
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'badge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to associate the page with',
			'wiki' => 'An identifier for the wiki on which the page resides',
			'title' => 'Title of the page to associate',
			'badge' => 'Badge to give to the page, ie "good" or "featured"',
		);
	}

	public function getDescription() {
		return array(
			'API module to associate an artcile on a wiki with a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbassociatearticle&id=42&wiki=en&title=Wikimedia'
				=> 'Set title "Wikimedia" for page with id "42" at English Wikipedia', // TODO: Still references Wikipedia
			'api.php?action=wbassociatearticle&id=42&wiki=en&title=Wikimedia&badge='
				=> 'Set title "Wikimedia" for page with id "42" at English Wikipedia and with a badge', // TODO: Still references Wikipedia
		);
	}
	
    public function getHelpUrls() {
    	return 'https://www.mediawiki.org/wiki/API:Wikidata#AssociateArticle';
    }
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
