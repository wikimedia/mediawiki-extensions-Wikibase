<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to get the data for one or more Wikibase items.
 *
 * @since 0.1
 *
 * @file
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
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		if ( !( isset( $params['ids'] ) XOR ( isset( $params['sites'] ) && isset( $params['titles'] ) ) ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-id-xor-wikititle' )->text(), 'id-xor-wikititle' );
		}

		$missing = 0;

		if ( !isset( $params['ids'] ) ) {
			$params['ids'] = array();
			$numSites = count( $params['sites'] );
			$numTitles = count( $params['titles'] );
			$max = max( $numSites, $numTitles );

			if ( $numSites === 0 || $numTitles === 0 ) {
				$this->dieUsage( $this->msg( 'wikibase-api-id-xor-wikititle' )->text(), 'id-xor-wikititle' );
			}
			else {
				$idxSites = 0;
				$idxTitles = 0;

				for ( $k = 0; $k < $max; $k++ ) {
					$siteId = $params['sites'][$idxSites++];
					$title = Utils::squashToNFC( $params['titles'][$idxTitles++] );

					$id = ItemHandler::singleton()->getIdForSiteLink( $siteId, $title );

					if ( $id ) {
						$params['ids'][] = intval( $id );
					}
					else {
						$this->getResult()->addValue( 'items', (string)(--$missing),
							array( 'site' => $siteId, 'title' => $title, 'missing' => "" )
						);
					}

					if ( $idxSites === $numSites ) {
						$idxSites = 0;
					}

					if ( $idxTitles === $numTitles ) {
						$idxTitles = 0;
					}
				}
			}
		}

		$params['ids'] = array_unique( $params['ids'], SORT_NUMERIC );

		$languages = $params['languages'];

		// This really needs a more generic solution as similar tricks will be
		// done to other props as well, for example variants for the language
		// attributes. It would also be nice to write something like */urls for
		// all props that can supply full urls.
		$siteLinkOptions = array();
		if ( in_array( 'sitelinks', $params['sort'] ) ) {
			$siteLinkOptions[] = $params['dir'];
		}
		if ( in_array( 'sitelinks/urls', $params['props'] ) ) {
			$siteLinkOptions[] = 'url';
			$props = array_flip( array_values( $params['props'] ) );
			unset( $props['sitelinks/urls'] );
			$props['sitelinks'] = true;
			$props = array_keys( $props );
		}
		else {
			$props = $params['props'];
		}
		if ( $siteLinkOptions === array() ) {
			$siteLinkOptions = null;
		}

		// loop over all items
		foreach ($params['ids'] as $id) {

			$itemPath = array( 'items', $id );
			$res = $this->getResult();

			$res->addValue( $itemPath, 'id', $id );

			// later we do a getContent but only if props are defined
			if ( $params['props'] !== array() ) {
				$page = ItemHandler::singleton()->getWikiPageForId( $id );

				if ( $page->exists() ) {
					// as long as getWikiPageForId only returns ids for legal items this holds
					/**
					 * @var $itemContent ItemContent
					 */
					$itemContent = $page->getContent();

					if ( is_null( $itemContent ) ) {
						continue;
					}

					$item = $itemContent->getItem();

					// loop over all props
					foreach ( $props as $key ) {
						switch ( $key ) {
						case 'info':
							$res->addValue( $itemPath, 'pageid', intval( $page->getId() ) );
							$title = $page->getTitle();
							$res->addValue( $itemPath, 'ns', intval( $title->getNamespace() ) );
							$res->addValue( $itemPath, 'title', $title->getPrefixedText() );
							$revision = $page->getRevision();
							if ( $revision !== null ) {
								$res->addValue( $itemPath, 'lastrevid', intval( $revision->getId() ) );
								$res->addValue( $itemPath, 'touched', wfTimestamp( TS_ISO_8601, $revision->getTimestamp() ) );
								$res->addValue( $itemPath, 'length', intval( $revision->getSize() ) );
							}
							$res->addValue( $itemPath, 'count', intval( $page->getCount() ) );
							break;
						case 'aliases':
							$this->addAliasesToResult( $item->getAllAliases( $languages ), $itemPath );
							break;
						case 'sitelinks':
							$this->addSiteLinksToResult( $item->getSiteLinks(), $itemPath, 'sitelinks', 'sitelink', $siteLinkOptions );
							break;
						case 'descriptions':
							$this->addDescriptionsToResult( $item->getDescriptions( $languages ), $itemPath );
							break;
						case 'labels':
							$this->addLabelsToResult( $item->getLabels( $languages ), $itemPath );
							break;
						default:
							// should never be here, because it should be something for the earlyer cases
							$this->dieUsage( $this->msg( 'wikibase-api-not-recognized' )->text(), 'not-recognized' );
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

		if ( $success && $params['gettoken'] ) {
			$user = $this->getUser();
			$this->addTokenToResult( $user->getEditToken() );
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'ids' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sites' => array(
				ApiBase::PARAM_TYPE => $this->getSiteLinkTargetSites()->getGlobalIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'sitelinks/urls' ),
				ApiBase::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sort' => array(
				// This could be done like the urls, where sitelinks/title sort on the title field
				// and sitelinks/site sort on the site code.
				ApiBase::PARAM_TYPE => array( 'sitelinks' ),
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'dir' => array(
				ApiBase::PARAM_TYPE => array( 'ascending', 'descending' ),
				ApiBase::PARAM_DFLT => 'ascending',
				ApiBase::PARAM_ISMULTI => false,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_ISMULTI => true,
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
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
			'sort' => array( 'The names of the properties to sort.',
				"Use together with 'dir' to give the sort order."
			),
			'dir' => array( 'The sort order for the given properties.',
				"Use together with 'sort' to give the properties to sort."
			),
			'languages' => array( 'By default the internationalized values are returned in all available languages.',
				'This parameter allows filtering these down to one or more languages by providing one or more language codes.'
			),
		) );
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to get the data for multiple Wikibase items.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'id-xor-wikititle', 'info' => $this->msg( 'wikibase-api-id-xor-wikititle' )->text() ),
			array( 'code' => 'no-such-item', 'info' => $this->msg( 'wikibase-api-no-such-item' )->text() ),
			array( 'code' => 'not-recognized', 'info' => $this->msg( 'wikibase-api-not-recognized' )->text() ),
		) );
	}

	/**
	 * @see ApiBase::getExamples()
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
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetitems';
	}

	/**
	 * @see ApiBase::getVersion()
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
