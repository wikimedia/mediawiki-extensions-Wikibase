<?php

namespace Wikibase\DataModel;

use Hashable;

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
	 * Note: we cannot implement Hashable interface by having this be getHash since PHP 5.4
	 * introduced a similarly named method in SplObjectStorage.
	 *
	 * @since 0.3
	 *
	 * @internal param MapHasher $mapHasher
	 *
	 * @return string
	 */
	public function getValueHash() {
		// We cannot have this as optional arg, because then we're no longer
		// implementing the Hashable interface properly according to PHP...
		$args = func_get_args();

		/**
		 * @var MapHasher $hasher
		 */
		$hasher = array_key_exists( 0, $args ) ? $args[0] : new MapValueHasher();

		return $hasher->hash( $this );
	}

	/**
	 * @see Comparable::equals
	 *
	 * The comparison is done purely value based, ignoring the order of the elements in the array.
	 *
	 * @since 0.3
	 *
	 * @param mixed $mixed
	 *
	 * @return boolean
	 */
	public function equals( $mixed ) {
		return is_object( $mixed )
			&& $mixed instanceof HashableObjectStorage
			&& $this->getValueHash() === $mixed->getValueHash();
	}

}
