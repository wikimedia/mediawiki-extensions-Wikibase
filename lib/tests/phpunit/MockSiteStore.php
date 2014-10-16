<?php

namespace Wikibase\Test;

use Site;
use SiteList;
use SiteStore;

/**
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MockSiteStore implements SiteStore {

	/**
	 * @var Site[]
	 */
	private $sites = array();

	/**
	 * Returns a SiteStore object that contains TestSites::getSites().
	 * The SiteStore is not not be backed by an actual database.
	 *
	 * @return SiteStore
	 */
	public static function newFromTestSites() {
		$store = new MockSiteStore( \TestSites::getSites() );
		return $store;
	}

	/**
	 * @param array $sites
	 */
	public function __construct( $sites = array() ) {
		$this->saveSites( $sites );
	}

	/**
	 * Saves the provided site.
	 *
	 * @since 1.21
	 *
	 * @param Site $site
	 *
	 * @return boolean Success indicator
	 */
	public function saveSite( Site $site ) {
		$this->sites[$site->getGlobalId()] = $site;
	}

	/**
	 * Saves the provided sites.
	 *
	 * @since 1.21
	 *
	 * @param Site[] $sites
	 *
	 * @return boolean Success indicator
	 */
	public function saveSites( array $sites ) {
		foreach ( $sites as $site ) {
			$this->saveSite( $site );
		}
	}

	/**
	 * Returns the site with provided global id, or null if there is no such site.
	 *
	 * @since 1.21
	 *
	 * @param string $globalId
	 * @param string $source either 'cache' or 'recache'.
	 *                       If 'cache', the values are allowed (but not obliged) to come from a cache.
	 *
	 * @return Site|null
	 */
	public function getSite( $globalId, $source = 'cache' ) {
		if ( isset( $this->sites[$globalId] ) ) {
			return $this->sites[$globalId];
		} else {
			return null;
		}
	}

	/**
	 * Returns a list of all sites. By default this site is
	 * fetched from the cache, which can be changed to loading
	 * the list from the database using the $useCache parameter.
	 *
	 * @since 1.21
	 *
	 * @param string $source either 'cache' or 'recache'.
	 *                       If 'cache', the values are allowed (but not obliged) to come from a cache.
	 *
	 * @return SiteList
	 */
	public function getSites( $source = 'cache' ) {
		return new SiteList( $this->sites );
	}

	/**
	 * Deletes all sites from the database. After calling clear(), getSites() will return an empty
	 * list and getSite() will return null until saveSite() or saveSites() is called.
	 */
	public function clear() {
		$this->sites = array();
	}

}
