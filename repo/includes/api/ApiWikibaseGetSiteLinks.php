<?php

/**
 * API module to get the link sites for a single Wikibase item.
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
class ApiWikibaseGetSiteLinks extends ApiWikibase {

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

		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			//$msg = 'id-xor-wikititle ' . $params['site'] . ' ' . $params['title '];
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		$success = false;

		if ( !isset( $params['id'] ) ) {
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}

		$page = WikibaseItem::getWikiPageForId( $params['id'] );
		if ( $page->exists() ) {
			// as long as getWikiPageForId only returns ids for legal items this holds
			$item = $page->getContent();
			if (is_null() ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
			if ( !( $item instanceof WikibaseItem ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
			}
			$res = $this->getResult();
			$res->addValue( null, 'item', array() );
			
			$sitelinks = $item->getRawSiteLinks();
			if (count($sitelinks)) {
				$res->addValue(
					'item',
					'sitelinks',
					$this->stripKeys( $params, $sitelinks, 'sl' )
				);
			}
			$res->addValue(
				'item',
				'id',
				$item->getId()
			);
		}
		else {
			// not  sure about this, its not conforming with other calls
			$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
		}

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
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseSites::singleton()->getIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
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
			'id' => 'The ID of the item to get the data from',
			'title' => array( 'The title of the corresponding page',
				"Use together with 'site'."
			),
			'site' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title'."
			),
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to get the data for a single Wikibase item.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'wrong-class', 'info' => wfMsg( 'wikibase-api-wrong-class' ) ),
			array( 'code' => 'id-xor-wikititle', 'info' => wfMsg( 'wikibase-api-id-xor-wikititle' ) ),
			array( 'code' => 'no-such-item-id', 'info' => wfMsg( 'wikibase-api-no-such-item-id' ) ),
			array( 'code' => 'no-such-item', 'info' => wfMsg( 'wikibase-api-no-such-item' ) ),
		) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetsitelinks&id=42'
			=> 'Get item number "42" and report the sitelinks',
			'api.php?action=wbgesitelinks&site=en&title=Berlin'
			=> 'Get the item associated to page "Berlin" on the site identified by "en" and report the sitelinks',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetlinksites';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
