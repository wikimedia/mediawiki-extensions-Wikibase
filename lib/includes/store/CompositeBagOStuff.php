<?php

namespace Wikibase;
use BagOStuff;

/**
 * CompositeBagOStuff
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class CompositeBagOStuff extends \BagOStuff {

	/**
	 * @var BagOStuff
	 */
	protected $primary;

	/**
	 * @var BagOStuff
	 */
	protected $secondary;

	/**
	 * @var int
	 */
	protected $autoExpiry;

	/**
	 * @param BagOStuff $primary
	 * @param BagOStuff $secondary
	 * @param int $autoExpiry Number of seconds ofter which an entry will expire that was
	 *        automatically added to the primary cahce after being found in the secondary cache.
	 */
	public function __construct( BagOStuff $primary, BagOStuff $secondary, $autoExpiry = 0 ) {
		$this->primary = $primary;
		$this->secondary = $secondary;
		$this->autoExpiry = $autoExpiry;
	}

	/**
	 * Tries to get a value from the primary cache or, of that fails,
	 * from the secondary cache.
	 *
	 * If the value was found in the secondary but not in the primary cache,
	 * it is copied to the primary cache before being returned.
	 *
	 * @see BagOStuff::get()
	 *
	 * @param $key string
	 * @param $casToken [optional] mixed
	 *
	 * @return mixed Returns false if the key was not found in neither primary nor secondary cache.
	 */
	public function get( $key, &$casToken = null ) {
		$value = $this->primary->get( $key, $casToken );

		if ( $value === false ) {
			$value = $this->secondary->get( $key, $casToken );

			if ( $value !== false ) {
				$this->primary->set( $key, $value, $this->autoExpiry );
			}
		}

		return $value;
	}

	/**
	 * Set a cache value in both the primary and the secondary cache.
	 *
	 * @see BagOStuff::set()
	 *
	 * @param $key string
	 * @param $value mixed
	 * @param int $exptime Either an interval in seconds or a unix timestamp for expiry
	 *
	 * @return bool success if either one of the primary or secondary caches returns success.
	 */
	public function set( $key, $value, $exptime = 0 ) {
		$primaryOk = $this->primary->set( $key, $value, $exptime );
		$secondaryOk = $this->secondary->set( $key, $value, $exptime );

		return $primaryOk || $secondaryOk;
	}

	/**
	 * Check and set a value in both the primary and secondary cache.
	 *
	 * @see BagOStuff::cas()
	 *
	 * @param $casToken mixed
	 * @param $key string
	 * @param $value mixed
	 * @param int $exptime Either an interval in seconds or a unix timestamp for expiry
	 *
	 * @return bool success if either one of the primary or secondary caches returns success.
	 */
	public function cas( $casToken, $key, $value, $exptime = 0 ) {
		$primaryOk = $this->primary->set( $casToken, $key, $value, $exptime );
		$secondaryOk = $this->secondary->set( $casToken, $key, $value, $exptime );

		return $primaryOk || $secondaryOk;
	}

	/**
	 * Delete a value from both the primary and secondary cache.
	 *
	 * @see BagOStuff::delete()
	 *
	 * @param $key string
	 * @param int $time Amount of time to delay the operation (mostly memcached-specific)
	 *
	 * @return bool success if both of the primary and secondary caches return success.
	 */
	public function delete( $key, $time = 0 ) {
		$primaryOk = $this->primary->delete( $key, $time );
		$secondaryOk = $this->secondary->delete( $key, $time );

		return $primaryOk && $secondaryOk;
	}

	/**
	 * Delete all objects expiring before a certain date, from both the primary and secondary cache.
	 *
	 * @see BagOStuff::deleteObjectsExpiringBefore()
	 *
	 * @param string $date The reference date in MW format
	 * @param $progressCallback callback|bool Optional, a function which will be called
	 *     regularly during long-running operations with the percentage progress
	 *     as the first parameter.
	 *
	 * @return bool success if both of the primary and secondary caches return success.
	 */
	public function deleteObjectsExpiringBefore( $date, $progressCallback = false ) {
		$primaryOk = $this->primary->deleteObjectsExpiringBefore( $date, $progressCallback );
		$secondaryOk = $this->secondary->deleteObjectsExpiringBefore( $date, $progressCallback );

		return $primaryOk && $secondaryOk;
	}
}
 