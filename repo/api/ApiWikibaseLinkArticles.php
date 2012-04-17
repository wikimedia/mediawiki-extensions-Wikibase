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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseLinkArticles extends ApiWikibaseModifyItem {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Actually modify the item.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected function modifyItem( WikibaseItem &$item, array $params ) {
		return $item->addSiteLink( $params['site_to'], $params['title_to'], !$params['noupdate'] );
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		$params['title'] = $params['title_from'];
		$params['site'] = $params['site_from'];
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
			),
			'site-from' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title-from' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'site-to' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title-to' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'noupdate' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
		) );
	}


	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'site-from' => array( 'Site code of the external page it is linked from.',
				"Use together with 'title-from'. Can also be used as 'site'."
			),
			'title-from' => array( 'Title of the page it is linked from',
				"Use together with 'site-from'. Can also be used as 'title'."
			),
			'site-to' => array( 'Site code of the external page it is linked to.',
				"Use together with 'title-to'."
			),
			'title-to' => array( 'Title of the page it is linked to.',
				"Use together with 'site-to'."
			),
			'noupdate' => 'Indicates that if a link to the specified site already exists, it should not be updated to use the provided page',
			//'change' => 'Indicates if you are adding or removing the link',
		) );
	}

	public function getDescription() {
		return array(
			'API module to use one article (site_from, title_from) to link to another article (site_to, title_to).'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wblinkarticles&site-from=en&title-from=Norway&site-to=no&title-to=Norge'
				=> array( 'Set link from title "Norway" for page in English Wikipedia to "Norge" in Norwegian Wikipedia.',
					"Actual form and type for 'site' is not final."
				),
			);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikidata#wblinkarticles';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
