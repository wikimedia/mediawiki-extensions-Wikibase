<?php

namespace Wikibase;
use ApiBase;

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
class ApiGetItemId extends Api {

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

		// normally 'id' should not exist here and the test should always return true
		if ( !isset( $params['id'] ) ) {
			$params['id'] = Item::getIdForSiteLink( $params['site'], $params['title'] );
			if ( $params['id'] === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}
		
		// the id should be checked for correct class, but we don't have a full item
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

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'site' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getIdentifiers(),
				//ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
		) );
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'title' => array(
				'The title of the external page that is used as an reference for the internal page.',
				'Must be used together with the identifier for the site where the page resides.'
			),
			'site' => array(
				'Site identifier for the external page that is used as an reference for the internal page.',
				'Must be used together with the title from the site where the page resides.'
			),
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to obtain the Wikibase id for a single item given a site and title pair.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'no-such-item', 'info' => wfMsg( 'wikibase-api-no-such-item' ) ),
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetitemid&site=en&title=Berlin'
				=> 'Get item id for page "Berlin" on the site identifierd by "en"',
		);
	}
	
	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Wikibase#wbgetitemid';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
