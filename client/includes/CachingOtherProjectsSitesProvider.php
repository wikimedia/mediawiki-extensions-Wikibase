<?php

namespace Wikibase\Client;

use BagOStuff;

/**
 * Get a list of sites that should be displayed in the "Other projects" sidebar
 * from cache or re-compute them.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class CachingOtherProjectsSitesProvider implements OtherProjectsSitesProvider {

	/**
	 * @var OtherProjectsSitesProvider
	 */
	private $otherProjectsSitesProvider;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $duration;

	/**
	 * @param OtherProjectsSitesProvider $otherProjectsSitesProvider
	 * @param BagOStuff $cache
	 * @param int $duration Cache duration
	 */
	public function __construct(
		OtherProjectsSitesProvider $otherProjectsSitesProvider,
		BagOStuff $cache,
		$duration
	) {
		$this->otherProjectsSitesProvider = $otherProjectsSitesProvider;
		$this->cache = $cache;
		$this->duration = $duration;
	}

	/**
	 * Get the site ids of other projects to use.
	 *
	 * @param string[] $siteLinkGroups
	 * @return string[]
	 */
	public function getOtherProjectsSiteIds( array $siteLinkGroups ) {
		$cacheKey = $this->getCacheKey( $siteLinkGroups );
		$siteIds = $this->cache->get( $cacheKey );

		if ( $siteIds === false ) {
			$siteIds = $this->generateAndCache( $cacheKey, $siteLinkGroups );
		}

		return $siteIds;
	}

	/**
	 * @param string $cacheKey
	 * @param string[] $siteLinkGroups
	 * @return string[]
	 */
	private function generateAndCache( $cacheKey, array $siteLinkGroups ) {
		$siteIds = $this->otherProjectsSitesProvider->getOtherProjectsSiteIds( $siteLinkGroups );
		$this->cache->set( $cacheKey, $siteIds, $this->duration );

		return $siteIds;
	}

	/**
	 * @param string[] $siteLinkGroups
	 * @return string
	 */
	private function getCacheKey( array $siteLinkGroups ) {
		$settingsHash = sha1( implode( '|', $siteLinkGroups ) );
		return wfMemcKey( 'OtherProjectsSites', $settingsHash );
	}
}
