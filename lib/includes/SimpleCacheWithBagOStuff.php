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
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function set( $key, $value, $ttl = null ) {
		$this->assertKeyIsValid( $key );
		$ttl = $this->normalizeTtl( $ttl );

		$value = $this->serialize( $value );

		return $this->inner->set( $this->prefix . $key, $value, $ttl );
	}

	/**
	 * @inheritDoc
	 */
	public function delete( $key ) {
		$this->assertKeyIsValid( $key );

		return $this->inner->delete( $this->prefix . $key );
	}

	/**
	 * @inheritDoc
	 */
	public function clear() {
		//Cannot be implemented
		return false;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
			throw $this->invalidArgument( "Cache key contains characters that are not allowed: `{$key}`" );
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
