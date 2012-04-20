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
 * @author John Erling Blad < jeblad@gmail.com >
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
		$params = $this->extractRequestParams();

		$success = false;

		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}

		$page = WikibaseItem::getWikiPageForId( $params['id'] );

		if ( $page->exists() ) {
			$item = $page->getContent();
		}
		else {
			$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
		}
		
		$this->getResult()->addValue(
			null,
			'page',
			array(
			 	'id' => $params['id']
			)
		);
		
		$success = true;

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	public function getAllowedParams() {
		return array(
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				/*ApiBase::PARAM_ISMULTI => true,*/
			),
		);
	}

	public function getParamDescription() {
		return array(
			'title' => 'The title of the page',
			'site' => 'Site identifier',
		);
	}

	public function getDescription() {
		return array(
			'API module to obtain the Wikibase ids of one or more pages on the specified site.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-contentmodel', 'info' => 'The content model of the page on which the item is stored is invalid' ),
			array( 'code' => 'no-such-item', 'info' => 'There are no such item to be found' ),
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitemid&site=en&title=Berlin'
				=> 'Get item id for page "Berlin" on the site identifierd by "en"',
		);
	}
	
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikidata#getitemid';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
