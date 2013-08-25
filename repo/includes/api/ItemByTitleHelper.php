<?php

namespace Wikibase\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\Repo\WikibaseRepo;

/**
 * Helper class for api modules to resolve page+title pairs into items.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class ItemByTitleHelper {
	/**
	 * @var \ApiBase
	 */
	protected $apiBase;

	/**
	 * @var \Wikibase\SiteLinkCache
	 */
	protected $siteLinkCache;

	/**
	 * @var \SiteStore
	 */
	protected $siteStore;

	/**
	 * @var \Wikibase\StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @param \ApiBase $apiBase
	 * @param \Wikibase\SiteLinkCache $siteLinkCache
	 * @param \SiteStore $siteStore
	 * @param \Wikibase\StringNormalizer $stringNormalizer
	 */
	public function __construct( \ApiBase $apiBase, \Wikibase\SiteLinkCache $siteLinkCache, \SiteStore $siteStore, \Wikibase\StringNormalizer $stringNormalizer ) {
		$this->apiBase = $apiBase;
		$this->siteLinkCache = $siteLinkCache;
		$this->siteStore = $siteStore;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * Tries to find entity ids for given client pages.
	 *
	 * @param array $sites
	 * @param array $titles
	 * @param bool $normalize
	 *
	 * @return array
	 */
	public function getEntityIds( array $sites, array $titles, $normalize ) {
		$ids = array();
		$numSites = count( $sites );
		$numTitles = count( $titles );

		if ( $normalize === true && max( $numSites, $numTitles ) > 1 ) {
			// For performance reasons we only do this if the user asked for it and only for one title!
			$this->apiBase->dieUsage(
				'Normalize is only allowed if exactly one site and one page have been given',
				'params-illegal'
			);
		}

		// Restrict the crazy combinations of sites and titles that can be used
		if( $numSites !== 1 && $numSites !== $numTitles ){
			$this->apiBase->dieUsage( 'Must request one site or an equal number of sites and titles','params-illegal' );
		}

		foreach( $sites as $siteId ){
			foreach( $titles as $title ){
				$entityId = $this->getEntiyId( $siteId, $title, $normalize );
				if( !is_null( $entityId ) ){
					$ids[] = $entityId;
				}
			}
		}

		return $ids;
	}

	/**
	 * Tries to find entity id for given siteId and title combination
	 *
	 * @param string $siteId
	 * @param string $title
	 * @param bool $normalize
	 *
	 * @return string|null
	 */
	private function getEntiyId( $siteId, $title, $normalize ) {
		$missing = 0;
		$title = $this->stringNormalizer->trimToNFC( $title );
		$id = $this->siteLinkCache->getItemIdForLink( $siteId, $title );

		// Try harder by requesting normalization on the external site.
		if ( $id === false && $normalize === true ) {
			$siteObj = $this->siteStore->getSite( $siteId );
			$id = $this->normalizeTitle( $title, $siteObj );
		}

		if ( $id === false ) {
			$this->apiBase->getResult()->addValue(
				'entities',
				(string)(--$missing),
				array( 'site' => $siteId, 'title' => $title, 'missing' => "" )
			);
		} else {
			return ItemId::newFromNumber( $id )->getPrefixedId();
		}
		return null;
	}

	/**
	 * Tries to normalize the given page title against the given client site.
	 * Updates $title accordingly and adds the normalization to the API output.
	 *
	 * @param string &$title
	 * @param \Site $site
	 *
	 * @return integer|boolean
	 */
	public function normalizeTitle( &$title, \Site $site ) {
		$normalizedTitle = $site->normalizePageName( $title );
		if ( $normalizedTitle !== false && $normalizedTitle !== $title ) {
			// Let the user know that we normalized
			$this->apiBase->getResult()->addValue(
				'normalized',
				'n',
				array( 'from' => $title, 'to' => $normalizedTitle )
			);

			$title = $normalizedTitle;
			return $this->siteLinkCache->getItemIdForLink( $site->getGlobalId(), $title );
		}

		return false;
	}
}
