<?php

namespace Wikibase\Api;

use Profiler;
use Site;
use SiteStore;
use UsageException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkCache;
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
	public function __construct(
		ResultBuilder $resultBuilder,
		SiteLinkCache $siteLinkCache,
		SiteStore $siteStore,
		StringNormalizer $stringNormalizer
	) {
		$this->resultBuilder = $resultBuilder;
		$this->siteLinkCache = $siteLinkCache;
		$this->siteStore = $siteStore;
		$this->stringNormalizer = $stringNormalizer;
	}

	/**
	 * Tries to find item ids for given client pages.
	 *
	 * @param array $sites
	 * @param array $titles
	 * @param bool $normalize
	 *
	 * @throws UsageException
	 * @return array( ItemId[], array() )
	 *         List containing valid ItemIds and MissingItem site title combinations
	 */
	public function getItemIds( array $sites, array $titles, $normalize ) {
		$ids = array();
		$numSites = count( $sites );
		$numTitles = count( $titles );

		// Make sure the arrays of sites and titles are not empty
		if ( $numSites === 0 || $numTitles === 0 ) {
			$this->throwUsageException(
				'Must request one site, one title, or an equal number of sites and titles',
				'param-missing'
			);
		}

		if ( $normalize && max( $numSites, $numTitles ) > 1 ) {
			// For performance reasons we only do this if the user asked for it and only for one title!
			$this->throwUsageException(
				'Normalize is only allowed if exactly one site and one page have been given',
				'params-illegal'
			);
		}

		// Restrict the crazy combinations of sites and titles that can be used
		if ( $numSites !== 1 && $numTitles !== 1 && $numSites !== $numTitles ) {
			$this->throwUsageException(
				'Must request one site, one title, or an equal number of sites and titles',
				'params-illegal'
			);
		}

		$missingItems = array();
		foreach( $sites as $siteId ) {
			foreach( $titles as $title ) {
				$itemId = $this->getItemId( $siteId, $title, $normalize );
				if( !is_null( $itemId ) ) {
					$ids[] = $itemId;
				} else {
					$missingItems[] = array( 'site' => $siteId, 'title' => $title );
				}
			}
		}

		return array( $ids, $missingItems );
	}

	/**
	 * Tries to find item id for given siteId and title combination
	 *
	 * @param string $siteId
	 * @param string $title
	 * @param bool $normalize
	 *
	 * @return ItemId|null
	 */
	private function getItemId( $siteId, $title, $normalize ) {
		$title = $this->stringNormalizer->trimToNFC( $title );
		$id = $this->siteLinkCache->getItemIdForLink( $siteId, $title );

		// Try harder by requesting normalization on the external site.
		if ( $id === null && $normalize === true ) {
			$siteObj = $this->siteStore->getSite( $siteId );
			//XXX: this passes the normalized title back into $title by reference...
			$this->normalizeTitle( $title, $siteObj );
			$id = $this->siteLinkCache->getItemIdForLink( $siteObj->getGlobalId(), $title );
		}

		return $id;
	}

	/**
	 * Tries to normalize the given page title against the given client site.
	 * Updates $title accordingly and adds the normalization to the API output.
	 *
	 * @param string &$title
	 * @param Site $site
	 */
	public function normalizeTitle( &$title, Site $site ) {
		$normalizedTitle = $site->normalizePageName( $title );
		if ( $normalizedTitle !== false && $normalizedTitle !== $title ) {
			// @todo this should not be in the helper but in the module itself...
			$this->resultBuilder->addNormalizedTitle( $title, $normalizedTitle );
			//XXX: the below is an ugly hack as we pass title by reference...
			$title = $normalizedTitle;
		}
	}

	/**
	 * @param $messgae
	 * @param $code
	 * @throws UsageException
	 */
	private function throwUsageException( $messgae, $code ) {
		Profiler::instance()->close();
		throw new UsageException( $messgae, $code );
	}
}
