<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Wikimedia\Assert\Assert;

/**
 * Simple CacheInterface that increments a statsd metric based on the number
 * of cache misses that occur.
 *
 *
 * @license GPL-2.0-or-later
 */
class StatsdRecordingSimpleCache implements CacheInterface {

	private const DEFAULT_VALUE = __CLASS__ . '-default';

	/** @var CacheInterface */
	private $inner;
	/** @var StatsdDataFactoryInterface */
	private $stats;
	/** @var string[] */
	private $statsKeys;

	/**
	 * @param CacheInterface $inner
	 * @param StatsdDataFactoryInterface $stats
	 * @param string[] $statsKeys
	 */
	public function __construct(
		CacheInterface $inner,
		StatsdDataFactoryInterface $stats,
		array $statsKeys
	) {
		Assert::parameter(
			array_key_exists( 'miss', $statsKeys ),
			'$statsKeys',
			'$statsKeys needs to have a \'miss\' value'
		);
		Assert::parameter(
			array_key_exists( 'hit', $statsKeys ),
			'$statsKeys',
			'$statsKeys needs to have a \'hit\' value'
		);
		$this->inner = $inner;
		$this->stats = $stats;
		$this->statsKeys = $statsKeys;
	}

	private function recordMisses( int $count ): void {
		$this->stats->updateCount( $this->statsKeys['miss'], $count );
	}

	private function recordHits( int $count ): void {
		$this->stats->updateCount( $this->statsKeys['hit'], $count );
	}

	public function get( $key, $default = null ) {
		$value = $this->inner->get( $key, self::DEFAULT_VALUE );
		if ( $value === self::DEFAULT_VALUE ) {
			$this->recordMisses( 1 );
			return $default;
		}

		$this->recordHits( 1 );
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
		$hits = 0;

		// This is using a reference because $values is just iterable
		// and might not be an array we can assign $values[$key] on.
		foreach ( $values as &$value ) {
			if ( $value === self::DEFAULT_VALUE ) {
				$misses++;
				$value = $default;
			} else {
				$hits++;
			}
		}
		unset( $value );

		if ( $misses !== 0 ) {
			$this->recordMisses( $misses );
		}
		if ( $hits !== 0 ) {
			$this->recordHits( $hits );
		}
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
