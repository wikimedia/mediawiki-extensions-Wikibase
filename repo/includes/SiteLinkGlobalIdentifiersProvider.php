<?php

namespace Wikibase\Repo;

use Psr\SimpleCache\CacheInterface;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkGlobalIdentifiersProvider {

	private const NO_VALUE = false;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param CacheInterface $cache
	 */
	public function __construct( SiteLinkTargetProvider $siteLinkTargetProvider, CacheInterface $cache ) {
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->cache = $cache;
	}

	public function getList( array $groups ): array {
		$cacheKey = 'list.' . implode( '_', $groups );
		$list = $this->cache->get(
			$cacheKey,
			self::NO_VALUE
		);
		if ( $list !== self::NO_VALUE ) {
			return $list;
		}

		$list = $this->siteLinkTargetProvider->getSiteList( $groups )->getGlobalIdentifiers();
		$this->cache->set( $cacheKey, $list, 3600 );
		return $list;
	}

}
