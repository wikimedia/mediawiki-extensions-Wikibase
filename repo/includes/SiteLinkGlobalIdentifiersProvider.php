<?php

namespace Wikibase\Repo;

use Psr\SimpleCache\CacheInterface;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkGlobalIdentifiersProvider {

	private const NO_VALUE = false;

	public function __construct(
		private readonly SiteLinkTargetProvider $siteLinkTargetProvider,
		private readonly CacheInterface $cache,
		private readonly array $siteLinkGroups,
	) {
	}

	public function getSiteIds(): array {
		$cacheKey = 'list.' . implode( '_', $this->siteLinkGroups );
		$list = $this->cache->get(
			$cacheKey,
			self::NO_VALUE
		);
		if ( $list !== self::NO_VALUE ) {
			return $list;
		}

		$list = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups )->getGlobalIdentifiers();
		$this->cache->set( $cacheKey, $list, 3600 );
		return $list;
	}

}
