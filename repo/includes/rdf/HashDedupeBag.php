<?php

namespace Wikibase;

/**
 * Null implementation of DedupeBag.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HashDedupeBag implements DedupeBag {

	/**
	 * @var array
	 */
	private $bag;

	/**
	 * @var int
	 */
	private $cutoff;

	/**
	 * @param int $cutoff The number of hash characters to use. A larger number means
	 *        less collisions, but a larger bag. The number can be read as an exponent
	 *        to the size of the hash's alphabet, so with a hex hash and $cutoff = 5,
	 *        you'd get a max bag size of 16^5, and a collision probability of 16^-5.
	 */
	public function __construct( $cutoff = 5 ) {
		$this->cutoff = $cutoff;

		$this->bag = array();
	}

	/**
	 * @see DedupeBag::alreadySeen
	 *
	 * Always returns false.
	 *
	 * @param string $hash
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function alreadySeen( $hash, $namespace = '' ) {
		$key = $namespace . substr( $hash, 0, $this->cutoff );
		if ( !isset( $this->bag[$key] ) || $this->bag[$key] !== $hash ) {
			$this->bag[$key] = $hash;
			return false;
		}

		return true;
	}

}
