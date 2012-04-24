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

		// our bail out if we can't identify an existing item
		if ( !isset( $params['id'] ) && !isset( $params['site'] ) && !isset( $params['title'] ) ) {
			$item = WikibaseItem::newEmpty();
			$success = $item->save();
			$params['id'] = $item->getId();
			if (!$success) {
				// a little bit odd error message
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}
		
		// because we commented out the required parameters we must test manually
		if ( !( isset( $params['ids'] ) XOR ( isset( $params['sites'] ) && isset( $params['titles'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}
		
		
		// normally 'id' should not exist here and the test should always return true
		// but as we have broken the normal thread in the previous clause this can be skipped
		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );
			if ( $params['id'] === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}
		
		$this->getResult()->addValue(
			null,
			'item',
			array( 'id' => $params['id'] )
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
				//ApiBase::PARAM_REQUIRED => true,
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				//ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'title' => array(
				'The title of the external page that is used as an reference for the internal page.',
				'Must be used together with the identifier for the site where the page resides.'
			),
			'site' => array(
				'Site identifier for the external page that is used as an reference for the internal page.',
				'Must be used together with the title from the site where the page resides.'
			),
		);
	}

	public function getDescription() {
		return array(
			'API module to obtain the Wikibase ids of one or more pages on the specified site.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
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
