<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SettingsArray;

/**
 * Simple cache wrapper for remote entities (items/properties/etc).
 *
 * Cache keys are namespaced by repository + entity ID.
 *
 * Settings:
 *  - federationEntityCacheTTL (int, seconds) – optional
 */
class RemoteEntityCache {

	private const CACHE_KEY_PREFIX = 'wb-federation-entity-';

	private CacheInterface $cache;
	private SettingsArray $settings;

	public function __construct(
		CacheInterface $cache,
		SettingsArray $settings
	) {
		$this->cache = $cache;
		$this->settings = $settings;
	}

	private function makeKey( string $repository, string $entityId ): string {
		return self::CACHE_KEY_PREFIX . $repository . '-' . $entityId;
	}

	private function getTtl(): ?int {
		if ( !$this->settings->hasSetting( 'federationEntityCacheTTL' ) ) {
			return null;
		}

		$ttl = $this->settings->getSetting( 'federationEntityCacheTTL' );

		return is_int( $ttl ) && $ttl > 0 ? $ttl : null;
	}

	/**
	 * @return array|null Remote entity data as returned from wbgetentities, or null on miss.
	 */
	public function get( string $repository, string $entityId ): ?array {
		$key = $this->makeKey( $repository, $entityId );
		$value = $this->cache->get( $key );

		return is_array( $value ) ? $value : null;
	}

	/**
	 * @param array $entityData Remote entity data (decoded wbgetentities entity blob)
	 */
	public function set( string $repository, string $entityId, array $entityData ): void {
		$key = $this->makeKey( $repository, $entityId );
		$this->cache->set( $key, $entityData, $this->getTtl() );
	}

	public function delete( string $repository, string $entityId ): void {
		$key = $this->makeKey( $repository, $entityId );
		$this->cache->delete( $key );
	}
}
