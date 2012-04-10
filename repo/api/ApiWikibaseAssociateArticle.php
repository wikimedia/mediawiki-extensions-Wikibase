<?php

/**
 * API module to associate an artcile on a Wikipedia with a Wikibase item.
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
			'language' => array(
				ApiBase::PARAM_TYPE => array(), // TODO: language code list
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
			'language' => 'Language code of the wikipedia on which the page resides',
			'title' => 'Title of the page to associate',
			'badge' => 'Badge to give to the page, ie "good" or "featured"',
		);
	}

	public function getDescription() {
		return array(
			'API module to associate an artcile on a Wikipedia with a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsetwikipediatitle&id=42&language=en&title=Wikimedia',
			'api.php?action=wbsetwikipediatitle&id=42&language=en&title=Wikimedia&badge=', // TODO
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
