<?php

namespace Wikibase;

/**
 * Generic array object with lookups based on hashes of the elements.
 *
 * Elements need to implement Hashable.
 *
 * Only a single element per hash can exist in the list. If the hash
 * of an element being added already exists in the list, the element
 * does not get added.
 *
 * Note that by default the getHash method uses @see MapValueHashesr
 * which returns a hash based on the contents of the list, regardless
 * of order and keys.
 *
 * Also note that if the Hashable elements are mutable, any modifications
 * made to them via their mutator methods will not cause an update of
 * their associated hash in this array.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class HashArray extends \GenericArrayObject implements Hashable {

	/**
	 * Maps element hashes to their offsets.
	 *
	 * @since 0.1
	 *
	 * @var array [ element hash (string) => element offset (string|int) ]
	 */
	protected $offsetHashes = array();

	/**
	 * @see GenericArrayObject::preSetElement
	 *
	 * @since 0.1
	 *
	 * @param int|string $index
	 * @param mixed $snak
	 *
	 * @return boolean
	 */
	protected function preSetElement( $index, $hashable ) {
		/**
		 * @var Hashable $hashable
		 */
		$hash = $hashable->getHash();

		if ( $this->hasElementHash( $hash ) ) {
			return false;
		}
		else {
			$this->offsetHashes[$hash] = $index;
			return true;
		}
	}

	/**
	 * Returns if there is an element with the provided hash.
	 *
	 * @since 0.1
	 *
	 * @param string $elementHash
	 *
	 * @return boolean
	 */
	public function hasElementHash( $elementHash ) {
		return array_key_exists( $elementHash, $this->offsetHashes );
	}

	/**
	 * Returns if there is an element with the same hash as the provided element in the list.
	 *
	 * @since 0.1
	 *
	 * @param Hashable $element
	 *
	 * @return boolean
	 */
	public function hasElement( Hashable $element ) {
		return $this->hasElementHash( $element->getHash() );
	}

	/**
	 * Removes the element with the hash of the provided element, if there is such an element in the list.
	 *
	 * @since 0.1
	 *
	 * @param Hashable $element
	 */
	public function removeElement( Hashable $element ) {
		$this->removeByElementHash( $element->getHash() );
	}

	/**
	 * Removes the element with the provided hash, if there is such an element in the list.
	 *
	 * @since 0.1
	 *
	 * @param string $elementHash
	 */
	public function removeByElementHash( $elementHash ) {
		if ( $this->hasElementHash( $elementHash ) ) {
			$this->offsetUnset( $this->offsetHashes[$elementHash] );
		}
	}

	/**
	 * Adds the provided element to the list if there is no element with the same hash yet.
	 *
	 * @since 0.1
	 *
	 * @param Hashable $element
	 *
	 * @return boolean Indicates if the element was added or not.
	 */
	public function addElement( Hashable $element ) {
		if ( $this->hasElementHash( $element->getHash() ) ) {
			return false;
		}
		else {
			$this->append( $element );
			return true;
		}
	}

	/**
	 * Returns the element with the provided hash or false if there is no such element.
	 *
	 * @since 0.1
	 *
	 * @param string $elementHash
	 *
	 * @return mixed|false
	 */
	public function getByElementHash( $elementHash ) {
		if ( $this->hasElementHash( $elementHash ) ) {
			return $this->offsetGet( $this->offsetHashes[$elementHash] );
		}
		else {
			return false;
		}
	}

	/**
	 * @see ArrayObject::offsetUnset
	 *
	 * @since 0.1
	 *
	 * @param mixed $index
	 */
	public function offsetUnset( $index ) {
		$element = $this->offsetGet( $index );

		if ( $element !== false ) {
			/**
			 * @var Hashable $element
			 */
			unset( $this->offsetHashes[$element->getHash()] );

			parent::offsetUnset( $index );
		}
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @param MapHasher $mapHasher
	 *
	 * @return string
	 */
	public function getHash() {
		// We cannot have this as optional arg, because then we're no longer
		// implementing the Hashable interface properly according to PHP...
		$args = func_get_args();

		/**
		 * @var MapHasher $hasher
		 */
		$hasher = array_key_exists( 0, $args ) ? $args[0] : new MapValueHasher();

		return $hasher->hash( $this->getArrayCopy() );
	}

}