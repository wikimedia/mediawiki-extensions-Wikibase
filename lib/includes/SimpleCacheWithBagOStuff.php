<?php

namespace Wikibase\Lib;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class SimpleCacheWithBagOStuff implements CacheInterface {

	use LoggerAwareTrait;

	const KEY_REGEX = '/^[a-zA-Z0-9_\-.]+\z/';

	/**
	 * @var \BagOStuff
	 */
	private $inner;

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * SimpleCacheWithBagOStuff constructor.
	 * @param \BagOStuff $inner
	 * @param string $prefix While setting and getting all keys will be prefixed with this string
	 * @param string $secret Will be used to create a signature for stored values
	 *
	 * @throws \InvalidArgumentException If prefix has wrong format or secret is not a string or empty
	 */
	public function __construct( \BagOStuff $inner, $prefix, $secret ) {
		$this->assertKeyIsValid( $prefix );

		if ( !is_string( $secret ) || empty( $secret ) ) {
			throw new \InvalidArgumentException( "Secret is required to be a nonempty string" );
		}

		$this->inner = $inner;
		$this->prefix = $prefix;
		$this->secret = $secret;
		$this->logger = new NullLogger();
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key The unique key of this item in the cache.
	 * @param mixed $default Default value to return if the key does not exist.
	 *
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if the $key string is not a legal value.
	 */
	public function get( $key, $default = null ) {
		$this->assertKeyIsValid( $key );

		$result = $this->inner->get( $this->prefix . $key );
		if ( $result === false ) {
			return $default;
		}

		return $this->unserialize( $result, $default, [ 'key' => $key, 'prefix' => $this->prefix ] );
	}

	/**
	 * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 * @param string $key The key of the item to store.
	 * @param mixed $value The value of the item to store, must be serializable.
	 * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *                                      the driver supports TTL then the library may set a default value
	 *                                      for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if the $key string is not a legal value.
	 */
	public function set( $key, $value, $ttl = null ) {
		$this->assertKeyIsValid( $key );
		$ttl = $this->normalizeTtl( $ttl );

		$value = $this->serialize( $value );

		return $this->inner->set( $this->prefix . $key, $value, $ttl );
	}

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @param string $key The unique cache key of the item to delete.
	 *
	 * @return bool True if the item was successfully removed. False if there was an error.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if the $key string is not a legal value.
	 */
	public function delete( $key ) {
		$this->assertKeyIsValid( $key );

		return $this->inner->delete( $this->prefix . $key );
	}

	/**
	 * Wipes clean the entire cache's keys.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function clear() {
		//Cannot be implemented
		return false;
	}

	/**
	 * Obtains multiple cache items by their unique keys.
	 *
	 * @param array|\Traversable $keys A list of keys that can obtained in a single operation.
	 * @param mixed $default Default value to return for keys that do not exist.
	 *
	 * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if $keys is neither an array nor a Traversable,
	 *   or if any of the $keys are not a legal value.
	 */
	public function getMultiple( $keys, $default = null ) {
		$keys = $this->toArray( $keys );
		$this->assertKeysAreValid( $keys );

		$prefixedKeys = array_map(
			function ( $k ) {
				return $this->prefix . $k;
			},
			$keys
		);

		$innerResult = $this->inner->getMulti( $prefixedKeys );
		$result = [];
		foreach ( $keys as $key ) {
			if ( !array_key_exists( $this->prefix . $key, $innerResult ) ) {
				$result[ $key ] = $default;
			} else {
				$result[ $key ] = $this->unserialize(
					$innerResult[ $this->prefix . $key ],
					$default,
					[ 'key' => $key, 'prefix' => $this->prefix ]
				);
			}
		}

		return $result;
	}

	/**
	 * Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 * @param array|\Traversable $values A list of key => value pairs for a multiple-set operation.
	 * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
	 *                                       the driver supports TTL then the library may set a default value
	 *                                       for it or let the driver take care of that.
	 *
	 * @return bool True on success and false on failure.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if $values is neither an array nor a Traversable,
	 *   or if any of the $values are not a legal value.
	 */
	public function setMultiple( $values, $ttl = null ) {
		$values = $this->toAssociativeArray( $values );

		$ttl = $this->normalizeTtl( $ttl );

		foreach ( $values as $key => $value ) {
			$values[ $this->prefix . $key ] = $this->serialize( $value );
		}

		return $this->inner->setMulti( $values, $ttl ?: 0 );
	}

	/**
	 * Deletes multiple cache items in a single operation.
	 *
	 * @param array|\Traversable $keys A list of string-based keys to be deleted.
	 *
	 * @return bool True if the items were successfully removed. False if there was an error.
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if $keys is neither an array nor a Traversable,
	 *   or if any of the $keys are not a legal value.
	 */
	public function deleteMultiple( $keys ) {
		$keys = $this->toArray( $keys );
		$this->assertKeysAreValid( $keys );
		$result = true;
		foreach ( $keys as $key ) {
			$result &= $this->delete( $key );
		}
		return (bool)$result;
	}

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * NOTE: It is recommended that has() is only to be used for cache warming type purposes
	 * and not to be used within your live applications operations for get/set, as this method
	 * is subject to a race condition where your has() will return true and immediately after,
	 * another script can remove it making the state of your app out of date.
	 *
	 * @param string $key The cache item key.
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 *   MUST be thrown if the $key string is not a legal value.
	 */
	public function has( $key ) {
		$this->assertKeyIsValid( $key );
		$result = $this->inner->get( $this->prefix . $key );
		return $result !== false;
	}

	private function assertKeysAreValid( $keys ) {
		foreach ( $keys as $key ) {
			$this->assertKeyIsValid( $key );
		}
	}

	/**
	 * @param mixed $key
	 * @param bool $allowIntegers Due to the fact that in PHP array indices are automatically casted
	 * 								to integers if possible, e.g. `['0' => ''] === [0 => '']`, we have to
	 * 								allow integers to be present as keys in $values in `setMultiple()`
	 * @throws InvalidArgumentException|\InvalidArgumentException
	 */
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

	/**
	 * @param $var
	 * @return array
	 * @throws InvalidArgumentException
	 */
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

	/**
	 * @param $var
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function toAssociativeArray( $var ) {
		if ( !$this->isIterable( $var ) ) {
			$type = gettype( $var );
			throw $this->invalidArgument( "Expected iterable, `{$type}` given" );
		}

		if ( $var instanceof \Traversable ) {
			$result = [];
			foreach ( $var as $key => $value ) {
				$this->assertKeyIsValid( $key, true );
				$result[ $key ] = $value;
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
	 * @return int UNIX timestamp when the item should expire or \BagOStuff::TTL_INDEFINITE
	 * @throws InvalidArgumentException
	 */
	private function normalizeTtl( $ttl ) {
		// Addition of `1` to timestamp is required to avoid the issue when we read timestamp in
		// the very end of the pending second (lets say 57.999) so that effective TTL becomes
		// very small (in former example it will be 0.001). This issue makes tests flaky.
		// @see https://phabricator.wikimedia.org/T201453
		if ( $ttl instanceof \DateInterval ) {
			$date = new \DateTime();
			$date->add( $ttl );
			return $date->getTimestamp() + 1;
		} elseif ( $ttl === 0 ) {
			return time();
		} elseif ( is_int( $ttl ) ) {
			return $ttl + time() + 1;
		} elseif ( $ttl === null ) {
			return \BagOStuff::TTL_INDEFINITE;
		} else {
			$type = gettype( $ttl );
			throw $this->invalidArgument( "Invalid TTL: `null|int|\DateInterval` expected, `$type` given" );
		}
	}

	private function serialize( $value ) {
		$serializedValue = serialize( $value );
		$dataToStore = utf8_encode( $serializedValue );

		$signature = hash_hmac( 'sha256', $dataToStore, $this->secret );
		return json_encode( [ $signature, $dataToStore ] );
	}

	/**
	 * @param string $string
	 * @param mixed $default
	 * @return mixed
	 * @throws \Exception
	 *
	 * @note This implementation is so complicated because we cannot use PHP serialization due to
	 *            the possibility of Object Injection attack.
	 *
	 * @see https://phabricator.wikimedia.org/T161647
	 * @see https://secure.php.net/manual/en/function.unserialize.php
	 * @see https://www.owasp.org/index.php/PHP_Object_Injection
	 */
	private function unserialize( $string, $default, array $loggingContext ) {
		$result = json_decode( $string );

		list( $signatureToCheck, $data ) = $result;
		$correctSignature = hash_hmac( 'sha256', $data, $this->secret );
		$hashEquals = hash_equals( $correctSignature, $signatureToCheck );
		if ( !$hashEquals ) {
			$this->logger->alert( "Incorrect signature", $loggingContext );

			return $default;
		}
		$decodedData = utf8_decode( $data );

		if ( $decodedData === serialize( false ) ) {
			return false;
		}

		// phpcs:disable Generic.PHP.NoSilencedErrors.Discouraged
		$value = @unserialize(
			$decodedData,
			[
				'allowed_classes' => [ \stdClass::class ]
			]
		);

		if ( $value === false ) {
			$this->logger->alert( "Cannot deserialize stored value", $loggingContext );

			return $default;
		}

		return $value;
	}

}
