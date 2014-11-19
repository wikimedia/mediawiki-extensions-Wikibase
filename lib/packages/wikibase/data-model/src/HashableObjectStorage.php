<?php

namespace Wikibase\DataModel;

use Hashable;
use Wikibase\DataModel\Internal\MapValueHasher;

/**
 * Object storage for Hashable objects.
 *
 * Note that this implementation is based on SplObjectStorage and
 * is not enforcing the type of objects set via it's native methods.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class HashableObjectStorage extends \SplObjectStorage implements \Comparable {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param array $objects
	 */
	public function __construct( array $objects = null ) {
		if ( $objects !== null ) {
			foreach ( $objects as $object ) {
				$this->attach( $object );
			}
		}
	}

	/**
	 * Removes duplicates bases on hash value.
	 *
	 * @since 0.2
	 */
	public function removeDuplicates() {
		$knownHashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $this ) as $hashable ) {
			$hash = $hashable->getHash();

			if ( in_array( $hash, $knownHashes ) ) {
				$this->detach( $hashable );
			}
			else {
				$knownHashes[] = $hash;
			}
		}
	}

	/**
	 * The hash is purely valuer based. Order of the elements in the array is not held into account.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getValueHash() {
		$hasher = new MapValueHasher();
		return $hasher->hash( $this );
	}

	/**
	 * @see Comparable::equals
	 *
	 * The comparison is done purely value based, ignoring the order of the elements in the array.
	 *
	 * @since 0.3
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $this->getValueHash() === $target->getValueHash();
	}

}
