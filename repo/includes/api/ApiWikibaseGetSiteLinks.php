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
class ApiWikibaseGetSiteLinks extends ApiBase {

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
			$msg = 'id-xor-wikititle ' . $params['site'] . ' ' . $params['title '];
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), $msg);
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
			$this->getResult()->addValue(
				null,
				'item',
				array(
				 	'id' => $params['id'],
					'sitelinks' => $item->getSiteLinks(),
				)
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

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to get the data from',
			'title' => array( 'The title of the corresponding page',
				"Use together with 'site'."
			),
			'site' => array( 'Identifier for the site on which the corresponding page resides',
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
			array( 'code' => 'id-xor-wikititle', 'info' => 'You need to either provide the item id or the title of a corresponding page and the identifier for the wiki this page is on' ),
			array( 'code' => 'no-such-item-id', 'info' => 'Could not find an existing item for this id' ),
			array( 'code' => 'no-such-item', 'info' => 'Could not find an existing item' ),
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbgetsitelinks&id=42'
			=> 'Get item number 42',
			'api.php?action=wbgesitelinks&site=en&title=Berlin'
			=> 'Get the item associated to page Berlin on the site identified by "en"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbgetlinksites';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
