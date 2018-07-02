<?php

namespace Wikibase\Lib;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use WANObjectCache;

/**
 * @license GPL-2.0-or-later
 */
class SimpleWANObjectCache implements CacheInterface {

	const KEY_REGEX = '/^[a-zA-Z0-9_\-.]+\z/';

	/**
	 * @var WANObjectCache
	 */
	private $innerCache;

	public function __construct( WANObjectCache $innerCache ) {
		$this->innerCache = $innerCache;
	}

	public function get( $key, $default = null ) {
		$this->assertKeyIsValid( $key );

		$result = $this->innerCache->get( $key );
		return $result !== false ? unserialize( $result ) : $default;
	}

	public function set( $key, $value, $ttl = null ) {
		$this->assertKeyIsValid( $key );

		return $this->innerCache->set( $key, serialize( $value ), $this->normalizeTtl( $ttl ) );
	}

	public function delete( $key ) {
		$this->assertKeyIsValid( $key );

		return $this->innerCache->delete( $key );
	}

	public function clear() {
		// Cannot be implemented
		return false;
	}

	public function getMultiple( $keys, $default = null ) {
		$keys = $this->toArray( $keys );
		$this->assertKeysAreValid( $keys );

		$innerResult = $this->innerCache->getMulti( $keys );
		$result = [];
		foreach ( $keys as $key ) {
			if ( !array_key_exists( $key, $innerResult ) ) {
				$result[$key] = $default;
			} else {
				$result[$key] = unserialize( $innerResult[$key] );
			}
		}
		return $result;
	}

	public function setMultiple( $values, $ttl = null ) {
		if ( !$this->isIterable( $values ) ) {
			$type = gettype( $values );
			throw $this->invalidArgument( "Expected iterable, `{$type}` given" );
		}

		$ttl = $this->normalizeTtl( $ttl );
		$result = true;
		foreach ( $values as $key => $value ) {
			$this->assertKeyIsValid( $key, true );
			$result &= $this->innerCache->set( $key, serialize( $value ), $ttl );
		}
		return (bool)$result;
	}

	public function deleteMultiple( $keys ) {
		$keys = $this->toArray( $keys );
		$this->assertKeysAreValid( $keys );

		$result = true;
		foreach ( $keys as $key ) {
			$this->innerCache->delete( $key );
		}
		return $result;
	}

	public function has( $key ) {
		$this->assertKeyIsValid( $key );

		return $this->innerCache->get( $key ) !== false;
	}

	private function assertKeysAreValid( $keys ) {
		foreach ( $keys as $key ) {
			$this->assertKeyIsValid( $key );
		}
	}

	private function assertKeyIsValid( $key, $allowIntegers = false ) {
		if ( $allowIntegers && is_int( $key ) ) {
			$key = (string)$key;
		}

		if ( !is_string( $key ) ) {
			$type = gettype( $key );
			throw $this->invalidArgument( "Cache key should be string or integer, `{$type}` is given" );
		}

		if ( $key === '' ) {
			throw $this->invalidArgument( "Cache key cannot be an empty string" );
		}

		if ( !preg_match( self::KEY_REGEX, $key ) ) {
			throw $this->invalidArgument( "Cache key contains characters that are not allowed" );
		}
	}

	private function invalidArgument( $message ) {
		return new class( $message ) extends \InvalidArgumentException
			implements InvalidArgumentException {
		};
	}

	private function toArray( $var ) {
		if ( !$this->isIterable( $var ) ) {
			$type = gettype( $var );
			throw $this->invalidArgument( "Expected iterable, `{$type}` given" );
		}

		if ( $var instanceof \Traversable ) {
			$result = [];
			foreach ( $var as $value ) {
				$result[] = $value;
			}
		} else {
			$result = $var;
		}

		return $result;
	}

	private function isIterable( $var ) {
		return is_array( $var ) || ( is_object( $var ) && ( $var instanceof \Traversable ) );
	}

	/**
	 * @param null|int|\DateInterval $ttl The TTL value of this item. If no value is sent and
	 *                                    the driver supports TTL then the library may set a default value
	 *                                    for it or let the driver take care of that.
	 *
	 * @return int Seconds to live or WANObjectCache::TTL_INDEFINITE
	 * @throws InvalidArgumentException
	 */
	private function normalizeTtl( $ttl ) {
		if ( $ttl instanceof \DateInterval ) {
			return $ttl->s;
		} elseif ( $ttl === 0 ) {
			// TODO: NASTY HACK. It is not int!!!!!!111
			// TTL 0 means immediately expired. For BagsOStuff 0 means "keep forever", so making it small enough
			return 0.00000001;
		} elseif ( is_int( $ttl ) ) {
			return $ttl;
		} elseif ( $ttl === null ) {
			return WANObjectCache::TTL_INDEFINITE;
		} else {
			$type = gettype( $ttl );
			throw $this->invalidArgument( "Invalid TTL: `null|int|\DateInterval` expected, `$type` given" );
		}
	}

}
