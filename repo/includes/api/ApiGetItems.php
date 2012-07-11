<?php

namespace Wikibase;
use ApiBase;

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
class ApiGetItems extends Api {

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

		$missing = 0;

		if ( !isset( $params['ids'] ) ) {
			$params['ids'] = array();
			if ( count($params['sites']) === 1 ) {
				$site = end( $params['sites'] );
				foreach ($params['titles'] as $title) {
					$title = Utils::squashToNFC( $title );
					$id = ItemContent::getIdForSiteLink( $site, $title );
					if ( $id ) {
						$params['ids'][] = intval( $id );
					}
					else {
						$this->getResult()->addValue( 'items', --$missing,
							array( 'site' => $site, 'title' => $title, 'missing' => "" )
						);
					}
				}
			}
			elseif ( count($params['titles']) === 1 ) {
				$title = Utils::squashToNFC( end( $params['titles'] ) );
				foreach ($params['sites'] as $site) {
					$id = ItemContent::getIdForSiteLink( $site, $title );
					if ( $id ) {
						$params['ids'][] = intval( $id );
					}
					else {
						$this->getResult()->addValue( 'items', --$missing,
							array( 'site' => $site, 'title' => $title, 'missing' => "" )
						);
					}
				}
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
			}
		}

		$languages = $params['languages'];

		$this->setUsekeys( $params );
		$this->setUseFallbacks( $params );

		foreach ($params['ids'] as $id) {

			$itemPath = array( 'items', $id );
			$res = $this->getResult();

			$res->addValue( $itemPath, 'id', $id );

			if ( $params['props'] !== array() ) {
				$page = ItemContent::getWikiPageForId( $id );
				if ( $page->exists() ) {
					// as long as getWikiPageForId only returns ids for legal items this holds
					$itemContent = $page->getContent();

					if ( is_null( $itemContent ) ) {
						continue;
					}

					if ( !( $itemContent instanceof ItemContent ) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
					}

					$item = $itemContent->getItem();

					foreach ( $params['props'] as $key ) {
						switch ( $key ) {
						case 'aliases':
							$this->addAliasesToResult(
								$item->getAllAliases( $languages ),
								$itemPath
							);
							break;
						case 'sitelinks':
							$this->addSiteLinksToResult(
								$item->getSiteLinks(),
								$itemPath
							);
							break;
						case 'descriptions':
							$useFallbacks = $this->usefallbacks;
							$this->addDescriptionsToResult(
								// secondary effect transferred through $useFallbacks
								$item->getDescriptions( $languages, $useFallbacks ),
								$itemPath,
								$useFallbacks
							);
							break;
						case 'labels':
							$useFallbacks = $this->usefallbacks;
							$this->addLabelsToResult(
								// secondary effect transferred through $useFallbacks
								$item->getLabels( $languages, $useFallbacks ),
								$itemPath,
								$useFallbacks
							);
							break;
						default:
							// should never be here, because it should be something for the earlyer cases
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
						}
					}
				}
				else {
					$this->getResult()->addValue( $itemPath, 'missing', "" );
				}
			}
		}
		$this->getResult()->setIndexedTagName_internal( array( 'items' ), 'item' );

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
				ApiBase::PARAM_TYPE => Sites::singleton()->getGlobalIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'sitelinks', 'aliases', 'labels', 'descriptions' ),
				ApiBase::PARAM_DFLT => 'sitelinks|aliases|labels|descriptions',
				ApiBase::PARAM_ISMULTI => true,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
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
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title', but only give one site for several titles or several sites for one title."
			),
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'sites', but only give one site for several titles or several sites for one title."
			),
			'props' => array( 'The names of the properties to get back from each item.',
				"Will be further filtered by any languages given."
			),
			'languages' => array( 'By default the internationalized values are returned in all available languages.',
				'This parameter allows filtering these down to one or more languages by providing one or more language codes.'
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
			array( 'code' => 'not-recognized', 'info' => wfMsg( 'wikibase-api-not-recognized' ) ),
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
			'api.php?action=wbgetitems&ids=42&languages=en'
			=> 'Get item number 42 with language attributes in English language',
			'api.php?action=wbgetitems&sites=en&titles=Berlin&languages=en'
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
