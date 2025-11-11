<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Minimal PSR-16 adapter around a BagOStuff instance.
 * Just enough for RemoteEntityCache.
 */
class BagOStuffSimpleCacheAdapter implements CacheInterface {

	private BagOStuff $bagOStuff;

	public function __construct( BagOStuff $bagOStuff ) {
		$this->bagOStuff = $bagOStuff;
	}

	public function get( $key, $default = null ) {
		$value = $this->bagOStuff->get( $key );

		if ( $value === false ) {
			return $default;
		}

		return $value;
	}

	public function set( $key, $value, $ttl = null ): bool {
		$ttlSeconds = $ttl === null ? 0 : (int)$ttl;

		return $this->bagOStuff->set( $key, $value, $ttlSeconds );
	}

	public function delete( $key ): bool {
		$this->bagOStuff->delete( $key );

		return true;
	}

	public function clear(): bool {
		// Not needed for our use case; no global clear.
		return false;
	}

	public function getMultiple( $keys, $default = null ): iterable {
		$result = [];
		foreach ( $keys as $key ) {
			$result[$key] = $this->get( $key, $default );
		}
		return $result;
	}

	public function setMultiple( $values, $ttl = null ): bool {
		foreach ( $values as $key => $value ) {
			$this->set( $key, $value, $ttl );
		}
		return true;
	}

	public function deleteMultiple( $keys ): bool {
		foreach ( $keys as $key ) {
			$this->delete( $key );
		}
		return true;
	}

	public function has( $key ): bool {
		return $this->bagOStuff->get( $key ) !== false;
	}
}
