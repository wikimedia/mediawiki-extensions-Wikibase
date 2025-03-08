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
	public function get( $key, $default = null ) {
		return $this->contents[$key] ?? $default;
	}

	/** @inheritDoc */
	public function set( $key, $value, $ttl = null ) {
		$this->contents[$key] = $value;
	}

	/** @inheritDoc */
	public function delete( $key ) {
		unset( $this->contents[$key] );
	}

	/** @inheritDoc */
	public function clear() {
		$this->contents = [];
	}

	/** @inheritDoc */
	public function has( $key ) {
		return isset( $this->contents[$key] );
	}

	/** @inheritDoc */
	public function getMultiple( $keys, $default = null ) {
		$entries = [];
		foreach ( $keys as $key ) {
			$entries[$key] = $this->get( $key );
		}

		return $entries;
	}

	/** @inheritDoc */
	public function setMultiple( $values, $ttl = null ) {
		foreach ( $values as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	/** @inheritDoc */
	public function deleteMultiple( $keys ) {
		throw new Exception( 'not yet implemented by test class ' );
	}

}
