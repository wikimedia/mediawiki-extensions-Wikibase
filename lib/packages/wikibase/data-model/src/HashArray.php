<?php

namespace Wikibase\DataModel;

use ArrayObject;
use Comparable;
use Hashable;
use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Internal\MapValueHasher;

/**
 * Generic array object with lookups based on hashes of the elements.
 *
 * Elements need to implement Hashable.
 *
 * Note that by default the getHash method uses @see MapValueHashesr
 * which returns a hash based on the contents of the list, regardless
 * of order and keys.
 *
 * Also note that if the Hashable elements are mutable, any modifications
 * made to them via their mutator methods will not cause an update of
 * their associated hash in this array.
 *
 * When acceptDuplicates is set to true, multiple elements with the same
 * hash can reside in the HashArray. Lookup by such a non-unique hash will
 * return only the first element and deletion will also delete only
 * the first such element.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class HashArray extends ArrayObject implements Hashable, Comparable {

	/**
	 * Maps element hashes to their offsets.
	 *
	 * @since 0.1
	 *
	 * @var array [ element hash (string) => array [ element offset (string|int) ] | element offset (string|int) ]
	 */
	protected $offsetHashes = array();

	/**
	 * If duplicate values (based on hash) should be accepted or not.
	 *
	 * @since 0.3
	 *
	 * @var bool
	 */
	protected $acceptDuplicates = false;

	/**
	 * @var integer
	 */
	protected $indexOffset = 0;

	/**
	 * Returns the name of an interface/class that the element should implement/extend.
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	abstract public function getObjectType();

	/**
	 * @see ArrayObject::__construct
	 *
	 * @param array|Traversable|null $input
	 * @param int $flags
	 * @param string $iteratorClass
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $input = null, $flags = 0, $iteratorClass = 'ArrayIterator' ) {
		parent::__construct( array(), $flags, $iteratorClass );

		if ( $input !== null ) {
			if ( !is_array( $input ) && !( $input instanceof Traversable ) ) {
				throw new InvalidArgumentException( '$input must be an array or Traversable' );
			}

			foreach ( $input as $offset => $value ) {
				$this->offsetSet( $offset, $value );
			}
		}
	}

	/**
	 * Finds a new offset for when appending an element.
	 * The base class does this, so it would be better to integrate,
	 * but there does not appear to be any way to do this...
	 *
	 * @return integer
	 */
	protected function getNewOffset() {
		while ( $this->offsetExists( $this->indexOffset ) ) {
			$this->indexOffset++;
		}

		return $this->indexOffset;
	}

	/**
	 * Gets called before a new element is added to the ArrayObject.
	 *
	 * At this point the index is always set (ie not null) and the
	 * value is always of the type returned by @see getObjectType.
	 *
	 * Should return a boolean. When false is returned the element
	 * does not get added to the ArrayObject.
	 *
	 * @since 0.1
	 *
	 * @param int|string $index
	 * @param Hashable $hashable
	 *
	 * @return bool
	 */
	protected function preSetElement( $index, $hashable ) {
		$hash = $hashable->getHash();

		$hasHash = $this->hasElementHash( $hash );

		if ( !$this->acceptDuplicates && $hasHash ) {
			return false;
		}
		else {
			if ( $hasHash ) {
				if ( !is_array( $this->offsetHashes[$hash] ) ) {
					$this->offsetHashes[$hash] = array( $this->offsetHashes[$hash] );
				}

				$this->offsetHashes[$hash][] = $index;
			}
			else {
				$this->offsetHashes[$hash] = $index;
			}

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
	 * @return bool
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
	 * @return bool
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
			$offset = $this->offsetHashes[$elementHash];

			if ( is_array( $offset ) ) {
				$offset = reset( $offset );
			}

			$this->offsetUnset( $offset );
		}
	}

	/**
	 * Adds the provided element to the list if there is no element with the same hash yet.
	 *
	 * @since 0.1
	 *
	 * @param Hashable $element
	 *
	 * @return bool Indicates if the element was added or not.
	 */
	public function addElement( Hashable $element ) {
		$append = $this->acceptDuplicates || !$this->hasElementHash( $element->getHash() );

		if ( $append ) {
			$this->append( $element );
		}

		return $append;
	}

	/**
	 * Returns the element with the provided hash or false if there is no such element.
	 *
	 * @since 0.1
	 *
	 * @param string $elementHash
	 *
	 * @return mixed|bool
	 */
	public function getByElementHash( $elementHash ) {
		if ( $this->hasElementHash( $elementHash ) ) {
			$offset = $this->offsetHashes[$elementHash];

			if ( is_array( $offset ) ) {
				$offset = reset( $offset );
			}

			return $this->offsetGet( $offset );
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
		if ( $this->offsetExists( $index ) ) {
			/**
			 * @var Hashable $element
			 */
			$element = $this->offsetGet( $index );

			$hash = $element->getHash();

			if ( array_key_exists( $hash, $this->offsetHashes )
				&& is_array( $this->offsetHashes[$hash] )
				&& count( $this->offsetHashes[$hash] ) > 1 ) {
				$this->offsetHashes[$hash] = array_filter(
					$this->offsetHashes[$hash],
					function( $value ) use ( $index ) {
						return $value !== $index;
					}
				);
			}
			else {
				unset( $this->offsetHashes[$hash] );
			}

			parent::offsetUnset( $index );
		}
	}

	/**
	 * @see Hashable::getHash
	 *
	 * The hash is purely valuer based. Order of the elements in the array is not held into account.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
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
			&& $this->getHash() === $target->getHash();
	}

	/**
	 * Removes duplicates bases on hash value.
	 *
	 * @since 0.3
	 */
	public function removeDuplicates() {
		$knownHashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $this ) as $hashable ) {
			$hash = $hashable->getHash();

			if ( in_array( $hash, $knownHashes ) ) {
				$this->removeByElementHash( $hash );
			}
			else {
				$knownHashes[] = $hash;
			}
		}
	}

	/**
	 * Returns if the hash indices are up to date.
	 * For an HashArray with immutable objects this should always be the case.
	 * For one with mutable objects it's the responsibility of the mutating code
	 * to keep the indices up to date (see class documentation) and thus possible
	 * this has not been done since the last update, thus causing a state where
	 * one or more indices are out of date.
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	public function indicesAreUpToDate() {
		foreach ( $this->offsetHashes as $hash => $offsets ) {
			$offsets = (array)$offsets;

			foreach ( $offsets as $offset ) {
				/** @var Hashable[] $this */
				if ( $this[$offset]->getHash() !== $hash ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Removes and adds all elements, ensuring the indices are up to date.
	 *
	 * @since 0.4
	 */
	public function rebuildIndices() {
		$hashables = iterator_to_array( $this );

		$this->offsetHashes = array();

		foreach ( $hashables as $offset => $hashable ) {
			$this->offsetUnset( $offset );
			$this->offsetSet( $offset, $hashable );
		}
	}

	/**
	 * @see ArrayObject::append
	 *
	 * @param mixed $value
	 */
	public function append( $value ) {
		$this->setElement( null, $value );
	}

	/**
	 * @see ArrayObject::offsetSet()
	 *
	 * @param mixed $index
	 * @param mixed $value
	 */
	public function offsetSet( $index, $value ) {
		$this->setElement( $index, $value );
	}

	/**
	 * Returns if the provided value has the same type as the elements
	 * that can be added to this ArrayObject.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected function hasValidType( $value ) {
		$class = $this->getObjectType();
		return $value instanceof $class;
	}

	/**
	 * Method that actually sets the element and holds
	 * all common code needed for set operations, including
	 * type checking and offset resolving.
	 *
	 * If you want to do additional indexing or have code that
	 * otherwise needs to be executed whenever an element is added,
	 * you can overload @see preSetElement.
	 *
	 * @param mixed $index
	 * @param mixed $value
	 *
	 * @throws InvalidArgumentException
	 */
	protected function setElement( $index, $value ) {
		if ( !$this->hasValidType( $value ) ) {
			$type = is_object( $value ) ? get_class( $value ) : gettype( $value );

			throw new InvalidArgumentException( '$value must be an instance of ' . $this->getObjectType() . '; got ' . $type );
		}

		if ( $index === null ) {
			$index = $this->getNewOffset();
		}

		if ( $this->preSetElement( $index, $value ) ) {
			parent::offsetSet( $index, $value );
		}
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array(
			'data' => $this->getArrayCopy(),
			'index' => $this->indexOffset,
		) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$serializationData = unserialize( $serialized );

		foreach ( $serializationData['data'] as $offset => $value ) {
			// Just set the element, bypassing checks and offset resolving,
			// as these elements have already gone through this.
			parent::offsetSet( $offset, $value );
		}

		$this->indexOffset = $serializationData['index'];
	}

	/**
	 * Returns if the ArrayObject has no elements.
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return !$this->getIterator()->valid();
	}

}
