<?php

namespace Wikibase\Lib;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Wikimedia\Assert\Assert;

/**
 * Simple CacheInterface that increments a statsd metric based on the number
 * of cache misses that occur.
 *
 * It might make sense to have this also record cache hits at some point, but
 * that was not needed for the usecase of the initial introduction.
 *
 * @license GPL-2.0-or-later
 */
class StatsdMissRecordingSimpleCache implements CacheInterface {

	/* private */const DEFAULT_VALUE = __CLASS__ . '-default';

	private $inner;
	private $stats;
	/** @var string */
	private $statsKey;

	/**
	 * @param CacheInterface $innner
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $statsKey
	 */
	public function __construct(
		CacheInterface $innner,
		StatsdDataFactoryInterface $stats,
		$statsKey
	) {
		Assert::parameterType( 'string', $statsKey, '$statsKey' );
		$this->inner = $innner;
		$this->stats = $stats;
		$this->statsKey = $statsKey;
	}

	private function recordMisses( $count ) {
		$this->stats->increment( $this->statsKey, $count );
	}

	public function get( $key, $default = null ) {
		$value = $this->inner->get( $key, self::DEFAULT_VALUE );
		if ( $value === self::DEFAULT_VALUE ) {
			$this->recordMisses( 1 );
			return $default;
		}
		return $value;
	}

	public function set( $key, $value, $ttl = null ) {
		return $this->inner->set( $key, $value, $ttl );
	}

	public function delete( $key ) {
		return $this->inner->delete( $key );
	}

	public function clear() {
		return $this->inner->clear();
	}

	public function getMultiple( $keys, $default = null ) {
		$values = $this->inner->getMultiple( $keys, self::DEFAULT_VALUE );
		$misses = 0;

		foreach ( $values as $key => $value ) {
			if ( $value === self::DEFAULT_VALUE ) {
				$misses++;
				$values[$key] = $default;
			}
		}

		$this->recordMisses( $misses );
		return $values;
	}

	public function setMultiple( $values, $ttl = null ) {
		return $this->inner->setMultiple( $values, $ttl );
	}

	public function deleteMultiple( $keys ) {
		return $this->inner->deleteMultiple( $keys );
	}

	public function has( $key ) {
		return $this->inner->has( $key );
	}

}
