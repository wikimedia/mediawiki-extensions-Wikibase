<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * CacheInterface test double
 *
 * @license GPL-2.0-or-later
 */
class FakeCache implements CacheInterface {

	/** @var array */
	private $contents = [];

	/** @inheritDoc */
	public function get( $key, $default = null ): mixed {
		return $this->contents[$key] ?? $default;
	}

	/** @inheritDoc */
	public function set( $key, $value, $ttl = null ): bool {
		$this->contents[$key] = $value;
		return true;
	}

	/** @inheritDoc */
	public function delete( $key ): bool {
		unset( $this->contents[$key] );
		return true;
	}

	/** @inheritDoc */
	public function clear(): bool {
		$this->contents = [];
		return true;
	}

	/** @inheritDoc */
	public function has( $key ): bool {
		return isset( $this->contents[$key] );
	}

	/** @inheritDoc */
	public function getMultiple( $keys, $default = null ): iterable {
		$entries = [];
		foreach ( $keys as $key ) {
			$entries[$key] = $this->get( $key );
		}

		return $entries;
	}

	/** @inheritDoc */
	public function setMultiple( $values, $ttl = null ): bool {
		foreach ( $values as $key => $value ) {
			$this->set( $key, $value );
		}
		return true;
	}

	/** @inheritDoc */
	public function deleteMultiple( $keys ): bool {
		throw new Exception( 'not yet implemented by test class ' );
	}

}
