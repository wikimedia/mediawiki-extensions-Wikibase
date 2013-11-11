<?php

namespace Wikibase\Api;

use Site;
use SiteStore;
use UsageException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\SiteLinkCache;
use Wikibase\StringNormalizer;

/**
 * Helper class for api modules to resolve page+title pairs into items.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 * @author Adam Shorland
 */
class ItemByTitleHelper {
	/**
	 * @var ResultBuilder
	 */
	protected $resultBuilder;

	/**
	 * @var SiteLinkCache
	 */
	protected $siteLinkCache;

	/**
	 * @var SiteStore
	 */
	protected $siteStore;

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @param ResultBuilder $resultBuilder
	 * @param SiteLinkCache $siteLinkCache
	 * @param SiteStore $siteStore
	 * @param StringNormalizer $stringNormalizer
	 */
	public function __construct( ResultBuilder $resultBuilder, SiteLinkCache $siteLinkCache, SiteStore $siteStore, StringNormalizer $stringNormalizer ) {
		$this->resultBuilder = $resultBuilder;
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
	 * @throws UsageException
	 * @return array
	 */
	public function getEntityIds( array $sites, array $titles, $normalize ) {
		$ids = array();
		$numSites = count( $sites );
		$numTitles = count( $titles );

		if ( $normalize && max( $numSites, $numTitles ) > 1 ) {
			// For performance reasons we only do this if the user asked for it and only for one title!
			throw new UsageException(
				'Normalize is only allowed if exactly one site and one page have been given',
				'params-illegal'
			);
		}

		// Restrict the crazy combinations of sites and titles that can be used
		if( $numSites !== 1 && $numSites !== $numTitles  ) {
			throw new UsageException(
				'Must request one site or an equal number of sites and titles',
				'params-illegal'
			);
		}

		foreach( $sites as $siteId ) {
			foreach( $titles as $title ) {
				$entityId = $this->getEntiyId( $siteId, $title, $normalize );
				if( !is_null( $entityId ) ) {
					$ids[] = $entityId;
				} else {
					//todo move this out of the helper...
					$this->resultBuilder->addMissingEntity( $siteId, $title );
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
		$title = $this->stringNormalizer->trimToNFC( $title );
		$id = $this->siteLinkCache->getItemIdForLink( $siteId, $title );

		// Try harder by requesting normalization on the external site.
		if ( $id === false && $normalize === true ) {
			$siteObj = $this->siteStore->getSite( $siteId );
			$id = $this->normalizeTitle( $title, $siteObj );
		}

		if ( $id === false ) {
			return null;
		} else {
			return ItemId::newFromNumber( $id )->getPrefixedId();
		}
	}

	/**
	 * Tries to normalize the given page title against the given client site.
	 * Updates $title accordingly and adds the normalization to the API output.
	 *
	 * @param string &$title
	 * @param Site $site
	 *
	 * @return integer|boolean
	 */
	public function normalizeTitle( &$title, Site $site ) {
		$normalizedTitle = $site->normalizePageName( $title );
		if ( $normalizedTitle !== false && $normalizedTitle !== $title ) {
			//@todo this should not be in the helper!
			$this->resultBuilder->addNormalizedTitle( $title, $normalizedTitle );
			return $this->siteLinkCache->getItemIdForLink( $site->getGlobalId(), $normalizedTitle );
		}

		return false;
	}
}
