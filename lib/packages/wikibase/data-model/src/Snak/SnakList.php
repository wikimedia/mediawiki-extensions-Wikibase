<?php

namespace Wikibase\DataModel\Snak;

use ArrayObject;
use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Internal\MapValueHasher;

/**
 * List of Snak objects.
 * Indexes the snaks by hash and ensures no more the one snak with the same hash are in the list.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 */
class SnakList extends ArrayObject {

	/**
	 * Maps snak hashes to their offsets.
	 *
	 * @var array [ snak hash (string) => snak offset (string|int) ]
	 */
	private $offsetHashes = [];

	/**
	 * @var int
	 */
	private $indexOffset = 0;

	/**
	 * @param Snak[]|Traversable $snaks
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $snaks = [] ) {
		if ( !is_array( $snaks ) && !( $snaks instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$snaks must be an array or an instance of Traversable' );
		}

		foreach ( $snaks as $index => $snak ) {
			$this->setElement( $index, $snak );
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return boolean
	 */
	public function hasSnakHash( $snakHash ) {
		return array_key_exists( $snakHash, $this->offsetHashes );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 */
	public function removeSnakHash( $snakHash ) {
		if ( $this->hasSnakHash( $snakHash ) ) {
			$offset = $this->offsetHashes[$snakHash];
			$this->offsetUnset( $offset );
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean Indicates if the snak was added or not.
	 */
	public function addSnak( Snak $snak ) {
		if ( $this->hasSnak( $snak ) ) {
			return false;
		}

		$this->append( $snak );
		return true;
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean
	 */
	public function hasSnak( Snak $snak ) {
		return $this->hasSnakHash( $snak->getHash() );
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 */
	public function removeSnak( Snak $snak ) {
		$this->removeSnakHash( $snak->getHash() );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return Snak|bool
	 */
	public function getSnak( $snakHash ) {
		if ( !$this->hasSnakHash( $snakHash ) ) {
			return false;
		}

		$offset = $this->offsetHashes[$snakHash];
		return $this->offsetGet( $offset );
	}

	/**
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
	 * The hash is purely value based. Order of the elements in the array is not held into account.
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
	 * Groups snaks by property, and optionally orders them.
	 *
	 * @param string[] $order List of property ID strings to order by. Snaks with other properties
	 *  will also be grouped, but put at the end, in the order each property appeared first in the
	 *  original list.
	 *
	 * @since 0.5
	 */
	public function orderByProperty( array $order = [] ) {
		$byProperty = array_fill_keys( $order, [] );

		/** @var Snak $snak */
		foreach ( $this as $snak ) {
			$byProperty[$snak->getPropertyId()->getSerialization()][] = $snak;
		}

		$ordered = [];
		foreach ( $byProperty as $snaks ) {
			$ordered = array_merge( $ordered, $snaks );
		}

		$this->exchangeArray( $ordered );

		$index = 0;
		foreach ( $ordered as $snak ) {
			$this->offsetHashes[$snak->getHash()] = $index++;
		}
	}

	/**
	 * Finds a new offset for when appending an element.
	 * The base class does this, so it would be better to integrate,
	 * but there does not appear to be any way to do this...
	 *
	 * @return int
	 */
	private function getNewOffset() {
		while ( $this->offsetExists( $this->indexOffset ) ) {
			$this->indexOffset++;
		}

		return $this->indexOffset;
	}

	/**
	 * @see ArrayObject::offsetUnset
	 *
	 * @since 0.1
	 *
	 * @param int|string $index
	 */
	public function offsetUnset( $index ): void {
		if ( $this->offsetExists( $index ) ) {
			/**
			 * @var Snak $element
			 */
			$element = $this->offsetGet( $index );
			$hash = $element->getHash();
			unset( $this->offsetHashes[$hash] );

			parent::offsetUnset( $index );
		}
	}

	/**
	 * @see ArrayObject::append
	 *
	 * @param Snak $value
	 */
	public function append( $value ): void {
		$this->setElement( null, $value );
	}

	/**
	 * @see ArrayObject::offsetSet()
	 *
	 * @param int|string $index
	 * @param Snak $value
	 */
	public function offsetSet( $index, $value ): void {
		$this->setElement( $index, $value );
	}

	/**
	 * Method that actually sets the element and holds
	 * all common code needed for set operations, including
	 * type checking and offset resolving.
	 *
	 * @param int|string $index
	 * @param Snak $value
	 *
	 * @throws InvalidArgumentException
	 */
	private function setElement( $index, $value ) {
		if ( !( $value instanceof Snak ) ) {
			throw new InvalidArgumentException( '$value must be a Snak' );
		}

		if ( $this->hasSnak( $value ) ) {
			return;
		}

		if ( $index === null ) {
			$index = $this->getNewOffset();
		}

		$hash = $value->getHash();
		$this->offsetHashes[$hash] = $index;
		parent::offsetSet( $index, $value );
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize(): string {
		return serialize( $this->__serialize() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ): void {
		$serializationData = unserialize( $serialized );
		$this->__unserialize( $serializationData );
	}

	/**
	 * @see https://wiki.php.net/rfc/custom_object_serialization
	 *
	 * @return array
	 */
	public function __serialize(): array {
		return [
			'data' => $this->getArrayCopy(),
			'index' => $this->indexOffset,
		];
	}

	/**
	 * @see https://wiki.php.net/rfc/custom_object_serialization
	 *
	 * @param array $data
	 */
	public function __unserialize( $data ): void {
		foreach ( $data['data'] as $offset => $value ) {
			// Just set the element, bypassing checks and offset resolving,
			// as these elements have already gone through this.
			parent::offsetSet( $offset, $value );
		}

		$this->indexOffset = $data['index'];
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
