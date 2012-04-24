<?php

/**
 * API module to get the data for a single Wikibase item.
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
class ApiWikibaseGetItems extends ApiBase {

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
					$params['ids'] = WikibaseItem::getIdForSiteLink( $params['sites'], $title );
				}
			}
			elseif ( count($params['titles']) === 1 ) {
				foreach ($params['sites'] as $site) {
					$params['ids'] = WikibaseItem::getIdForSiteLink( $sites, $params['titles'] );
				}
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
			}
			
			if ( count($params['ids']) === 0 ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
			}
		}

		$languages = WikibaseUtils::getLanguageCodes();
		
		$this->getResult()->addValue(
			null,
			'items',
			array()
		);
		
		foreach ($params['ids'] as $id) {
			WikibaseItem::getWikiPageForId( $id );
			if ($page->exists()) {
				// as long as getWikiPageForId only returns ids for legal items this holds
				$item = $page->getContent();
				$this->getResult()->addValue(
					'items',
					"{$id}",
					array(
					 	'id' => $id,
						'sitelinks' => $item->getSiteLinks(),
						'descriptions' => $item->getDescriptions($languages),
						'labels' => $item->getLabels($languages),
					)
				);
			}
		}
		
		$success = true;

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	public function getAllowedParams() {
		return array(
			'ids' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sites' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'ids' => 'The ID of the item to get the data from',
			'language' => 'By default the internationalized values are returned in all available languages.
						This parameter allows filtering these down to one or more languages by providing their language codes.',
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'site'."
			),
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title'."
			),
		);
	}

	public function getDescription() {
		return array(
			'API module to get the data for a single Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => 'You need to either provide the item ids or the titles of a corresponding page and the identifier for the wiki this page is on' ),
			array( 'code' => 'no-such-item-id', 'info' => 'Could not find an existing item for this id' ),
			array( 'code' => 'no-such-item', 'info' => 'Could not find an existing item' ),
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetitem&ids=42'
			=> 'Get item number 42 with default (user?) language',
			'api.php?action=wbgetitem&ids=42&language=en'
			=> 'Get item number 42 with english language',
			'api.php?action=wbgetitem&sites=en&titles=Berlin&language=en'
			=> 'Get the item associated to page Berlin on the site identified by "en"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbgetitem';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
