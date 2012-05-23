<?php

/**
 * API module to get the data for one or more Wikibase items.
 *
 * @since 0.1
 *
 * @file ApiWikibaseGetItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseGetItems extends ApiWikibase {

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

		if ( !( isset( $params['ids'] ) XOR ( isset( $params['sites'] ) && isset( $params['titles'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		$success = false;

		if ( !isset( $params['ids'] ) ) {
			$params['ids'] = array();
			if ( count($params['sites']) === 1 ) {
				foreach ($params['titles'] as $title) {
					$params['ids'][] = WikibaseItem::getIdForSiteLink( $params['sites'], $title );
				}
			}
			elseif ( count($params['titles']) === 1 ) {
				foreach ($params['sites'] as $site) {
					$params['ids'][] = WikibaseItem::getIdForSiteLink( $sites, $params['titles'] );
				}
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
			}
			
			if ( count($params['ids']) === 0 ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}

		$languages = $params['languages'];
		
/*		$this->getResult()->addValue(
			null,
			'items',
			array()
		);
*/		
		foreach ($params['ids'] as $id) {
			$page = WikibaseItem::getWikiPageForId( $id );
			if ($page->exists()) {
				// as long as getWikiPageForId only returns ids for legal items this holds
				$item = $page->getContent();
				if ( is_null($item) ) {
					continue;
				}
				if ( !( $item instanceof WikibaseItem ) ) {
					$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
				}
				
				// this is not a very nice way to transfer the values
				// but if there are only a few calls its okey
				$res = $this->getResult();
				$arr = array();
				//$arr = array( 'id' => $id );
				$sitelinks = $this->stripKeys( $params, $item->getRawSiteLinks(), 'sl' );
				if (count($sitelinks)) {
					$arr['sitelinks'] = $sitelinks;
				}
				$descriptions = $this->stripKeys( $params, $item->getRawDescriptions( $languages ), 'd' );
				if (count($descriptions)) {
					$arr['descriptions'] = $descriptions;
				}
				$labels = $this->stripKeys( $params, $item->getRawLabels( $languages ), 'l' );
				if (count($labels)) {
					$arr['labels'] = $labels;
				}
				
				$arr['id'] = $item->getId();
				
				$res->addValue(
					'items',
					"{$id}",
					$arr
				);
				$res->setIndexedTagName_internal( array( 'items' ), 'item' );
			}
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
			'ids' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sites' => array(
				ApiBase::PARAM_TYPE => WikibaseSites::singleton()->getIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
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
			'ids' => 'The IDs of the items to get the data from',
			'languages' => 'By default the internationalized values are returned in all available languages.
						This parameter allows filtering these down to one or more languages by providing their language codes.',
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'sites', but only give one site for several titles or several sites for one title."
			),
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title', but only give one site for several titles or several sites for one title."
			),
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to get the data for multiple Wikibase items.'
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
			array( 'code' => 'no-such-item', 'info' => wfMsg( 'wikibase-api-no-such-item' ) ),
		) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetitems&ids=42'
			=> 'Get item number 42 with language attributes in all available languages',
			'api.php?action=wbgetitems&ids=42&language=en'
			=> 'Get item number 42 with language attributes in English language',
			'api.php?action=wbgetitems&sites=en&titles=Berlin&language=en'
			=> 'Get the item for page "Berlin" on the site "en", with language attributes in English language',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetitems';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
