<?php

namespace Wikibase\Client;

use BagOStuff;

/**
 * Get a list of sites that should be displayed in the "Other projects" sidebar
 * from cache or re-compute them.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class CachingOtherProjectsSitesProvider implements OtherProjectsSitesProvider {

	/**
	 * @var OtherProjectsSitesProvider
	 */
	private $provider;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var int Time-to-live (in seconds)
	 */
	private $ttl;

	/**
	 * @param OtherProjectsSitesProvider $provider
	 * @param BagOStuff $cache
	 * @param int $ttl Cache duration
	 */
	public function __construct(
		OtherProjectsSitesProvider $provider,
		BagOStuff $cache,
		$ttl
	) {
		$this->provider = $provider;
		$this->cache = $cache;
		$this->ttl = $ttl;
	}

	/**
	 * Get the site ids of other projects to use.
	 *
	 * @param string[] $siteLinkGroups
	 * @return string[]
	 */
	public function getOtherProjectsSiteIds( array $siteLinkGroups ) {
		$settingsHash = sha1( implode( '|', $siteLinkGroups ) );

		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'OtherProjectsSites', $settingsHash ),
			$this->ttl,
			function () use ( $siteLinkGroups ) {
				return $this->provider->getOtherProjectsSiteIds( $siteLinkGroups );
			}
		);
	}

}
