<?php

namespace Wikibase\Repo\Rdf;

/**
 * Hash based implementation of DedupeBag.
 *
 * This implementation of DedupeBag operates like a rather lossy cache; it's implemented
 * as a hash that just evicts old values when a collision occurs.
 *
 * The idea for this implementation was taken mainly from from blog posts:
 * - "The Invertible Bloom Filter" by Mike James
 *   http://www.i-programmer.info/programming/theory/4641-the-invertible-bloom-filter.html
 * - "The Opposite of a Bloom Filter" by Jeff Hodges
 *   http://www.somethingsimilar.com/2012/05/21/the-opposite-of-a-bloom-filter/
 *
 * The implementation of alreadySeen() works as follows:
 *
 * - Determine $key be truncating the $hash parameter, and prepending the $namespace to it. The
 *   point of truncation is governed by the $cutoff setting in the parameter, and is used to
 *   govern the tradeoff between bag size and the likelihood of false negatives.
 *
 * - Look up $key in $this->bag. If $this->bag[$key] is not set, return false, indicating that the
 *   value (combination of $hash and $namespace) has never been seen before (we are sure in this
 *   case).
 *
 * - If $this->bag[$key] is set, compare the value stored there with $hash. If they are the same,
 *   return true, to indicate that the value has been seen before.
 *
 * - If $hash is different from $this->bag[$key], the value might have been seen before but
 *   been evicted due to a collision, or not. In this case, return false, to be sure. This is
 *   safe since the contract of alreadySeen() states that false negatives are admissible.
 *
 * - In all cases, before returning anything, set $this->bag[$key] = $hash.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HashDedupeBag implements DedupeBag {

	/**
	 * @var string[]
	 */
	private $bag;

	/**
	 * @var int
	 */
	private $cutoff;

	/**
	 * Constructs a new HashDedupeBag with the given cutoff value, which is the
	 * number of hash characters to use. A larger number means less collisions
	 * (fewer false negatives), but a larger bag. The number can be read as an
	 * exponent to the size of the hash's alphabet, so with a hex hash and $cutoff = 5,
	 * you'd get a max bag size of 16^5, and a collision probability of 16^-5 = 1/32.
	 *
	 * @param int $cutoff
	 */
	public function __construct( $cutoff = 5 ) {
		$this->cutoff = $cutoff;

		$this->bag = [];
	}

	/**
	 * @see DedupeBag::alreadySeen
	 *
	 * Returns true if the given combination of $hash and $namespace has been seen before -
	 * that is, alreadySeen() had already been called on this HashDedupeBag with the same values
	 * for $hash and $namespace. Returning false is inconclusive: The hash and namespace
	 * may or may not have been seen before, false negatives are possible. The probability
	 * of a false negatives here can be controlled using the $cutoff parameter passed to the
	 * constructor.
	 *
	 * See the class level documentation for an explanation of the algorithm.
	 *
	 * @param string $hash
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function alreadySeen( $hash, $namespace = '' ) {
		$key = $namespace . substr( $hash, 0, $this->cutoff );

		if ( array_key_exists( $key, $this->bag ) && $this->bag[$key] === $hash ) {
			return true;
		}

		$this->bag[$key] = $hash;
		return false;
	}

}
