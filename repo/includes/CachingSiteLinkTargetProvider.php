<?php

namespace Wikibase\Repo;

use BagOStuff;
use MWException;
use ObjectCache;
use Site;
use SiteList;
use SiteStore;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class CachingSiteLinkTargetProvider extends SiteLinkTargetProvider {

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @param SiteStore $siteStore
	 * @param BagOStuff $cache
	 * @param string[] $specialSiteGroups
	 */
	public function __construct(
		SiteStore $siteStore,
		BagOStuff $cache,
		array $specialSiteGroups = array()
	) {
		parent::__construct( $siteStore, $specialSiteGroups );
		$this->cache = $cache;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @return SiteList alphabetically ordered by the site's global identifiers.
	 */
	public function getSiteList() {
		$cacheKey = $this->cache->makeKey( __CLASS__, 'getSiteList' );
		$sites = $this->cache->get( $cacheKey );

		if ( !$sites ) {
			$sites = parent::getSiteList();

			// Because of the way SiteList is implemented this will not order the array returned by
			// SiteList::getGlobalIdentifiers.
			$sites->uasort( function( Site $a, Site $b ) {
				return strnatcasecmp( $a->getGlobalId(), $b->getGlobalId() );
			} );

			$this->cache->set( $cacheKey, $sites, 300 );
		}

		return $sites;
	}

	/**
	 * Clear the cache used by this class/.
	 * Should only be used from within tests
	 *
	 * @throws MWException
	 */
	public static function clearCache() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new MWException( 'Cannot run ' . __METHOD__ . ' outside of tests.' );
		}

		$accelCache = ObjectCache::getInstance( CACHE_ACCEL );
		$accelCache->delete(
			$accelCache->makeKey( __CLASS__, 'getSiteList' )
		);
	}

}
