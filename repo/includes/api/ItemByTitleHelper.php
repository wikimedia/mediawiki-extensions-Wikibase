<?php

namespace Wikibase\Api;

use Wikibase\DataModel\Entity\EntityId;
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
		$missing = 0;
		$numSites = count( $sites );
		$numTitles = count( $titles );
		$max = max( $numSites, $numTitles );

		if ( $normalize === true && $max > 1 ) {
			// For performance reasons we only do this if the user asked for it and only for one title!
			$this->apiBase->dieUsage(
				'Normalize is only allowed if exactly one site and one page have been given',
				'params-illegal'
			);
		}

		$idxSites = 0;
		$idxTitles = 0;

		for ( $k = 0; $k < $max; $k++ ) {
			$siteId = $sites[$idxSites++ % $numSites];
			$title = $this->stringNormalizer->trimToNFC( $titles[$idxTitles++ % $numTitles] );

			$id = $this->siteLinkCache->getItemIdForLink( $siteId, $title );

			// Try harder by requesting normalization on the external site.
			if ( $id === false && $normalize === true ) {
				$siteObj = $this->siteStore->getSite( $siteId );
				$id = $this->normalizeTitle( $title, $siteObj );
			}

			if ( $id === false ) {
				$this->apiBase->getResult()->addValue( 'entities', (string)(--$missing),
					array( 'site' => $siteId, 'title' => $title, 'missing' => "" )
				);
			} else {
				$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

				$id = ItemId::newFromNumber( $id );
				$ids[] = $entityIdFormatter->format( $id );
			}
		}

		return $ids;
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
