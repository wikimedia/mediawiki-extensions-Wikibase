<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Serializable;
use Traversable;
use Wikibase\DataModel\Internal\MapValueHasher;
use Wikibase\DataModel\Snak\Snak;

/**
 * List of Reference objects.
 *
 * @since 0.1
 * Does not implement References anymore since 2.0
 * Does not extend SplObjectStorage since 5.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Kreuz
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferenceList implements Countable, IteratorAggregate, Serializable {

	/**
	 * @var Reference[] Ordered list or references, indexed by SPL object hash.
	 */
	private $references = [];

	/**
	 * @param Reference[]|Traversable $references
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $references = [] ) {
		if ( !is_array( $references ) && !( $references instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$references must be an array or an instance of Traversable' );
		}

		foreach ( $references as $reference ) {
			if ( !( $reference instanceof Reference ) ) {
				throw new InvalidArgumentException( 'Every element in $references must be an instance of Reference' );
			}

			$this->addReference( $reference );
		}
	}

	/**
	 * Adds the provided reference to the list.
	 * Empty references are ignored.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 * @param int|null $index New position of the added reference, or null to append.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addReference( Reference $reference, $index = null ) {
		if ( $index !== null && ( !is_int( $index ) || $index < 0 ) ) {
			throw new InvalidArgumentException( '$index must be a non-negative integer or null' );
		}

		if ( $reference->isEmpty() ) {
			return;
		}

		$splHash = spl_object_hash( $reference );

		if ( array_key_exists( $splHash, $this->references ) ) {
			return;
		}

		if ( $index === null || $index >= count( $this->references ) ) {
			// Append object to the end of the reference list.
			$this->references[$splHash] = $reference;
		} else {
			$this->insertReferenceAtIndex( $reference, $index );
		}
	}

	/**
	 * @since 1.1
	 *
	 * @param Snak ...$snaks
	 * (passing a single Snak[] is still supported but deprecated)
	 *
	 * @throws InvalidArgumentException
	 */
	public function addNewReference( ...$snaks ) {
		if ( count( $snaks ) === 1 && is_array( $snaks[0] ) ) {
			// TODO stop supporting this
			$snaks = $snaks[0];
		}

		$this->addReference( new Reference( $snaks ) );
	}

	/**
	 * @param Reference $reference
	 * @param int $index
	 */
	private function insertReferenceAtIndex( Reference $reference, $index ) {
		if ( !is_int( $index ) ) {
			throw new InvalidArgumentException( '$index must be an integer' );
		}

		$splHash = spl_object_hash( $reference );

		$this->references = array_merge(
			array_slice( $this->references, 0, $index ),
			[ $splHash => $reference ],
			array_slice( $this->references, $index )
		);
	}

	/**
	 * Returns if the list contains a reference with the same hash as the provided reference.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return bool
	 */
	public function hasReference( Reference $reference ) {
		return $this->hasReferenceHash( $reference->getHash() );
	}

	/**
	 * Returns the index of the Reference object or false if the Reference could not be found.
	 *
	 * @since 0.5
	 *
	 * @param Reference $reference
	 *
	 * @return int|bool
	 */
	public function indexOf( Reference $reference ) {
		$index = 0;

		foreach ( $this->references as $ref ) {
			if ( $ref === $reference ) {
				return $index;
			}

			$index++;
		}

		return false;
	}

	/**
	 * Removes the reference with the same hash as the provided reference if such a reference exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference ) {
		$this->removeReferenceHash( $reference->getHash() );
	}

	/**
	 * Returns if the list contains a reference with the provided hash.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return bool
	 */
	public function hasReferenceHash( $referenceHash ) {
		return $this->getReference( $referenceHash ) !== null;
	}

	/**
	 * Looks for the first Reference object in this list with the provided hash.
	 * Removes all occurences of that object.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash	`
	 */
	public function removeReferenceHash( $referenceHash ) {
		$reference = $this->getReference( $referenceHash );

		if ( $reference === null ) {
			return;
		}

		foreach ( $this->references as $splObjectHash => $ref ) {
			if ( $ref === $reference ) {
				unset( $this->references[$splObjectHash] );
			}
		}
	}

	/**
	 * Returns the first Reference object with the provided hash, or
	 * null if there is no such reference in the list.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|null
	 */
	public function getReference( $referenceHash ) {
		foreach ( $this->references as $reference ) {
			if ( $reference->getHash() === $referenceHash ) {
				return $reference;
			}
		}

		return null;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 2.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array_values( $this->references ) );
	}

	/**
	 * @see https://wiki.php.net/rfc/custom_object_serialization
	 *
	 * @return array
	 */
	public function __serialize() {
		return [
			'references' => array_values( $this->references ),
		];
	}

	/**
	 * @see https://wiki.php.net/rfc/custom_object_serialization
	 *
	 * @param array $data
	 */
	public function __unserialize( array $data ): void {
		$this->references = $data['references'];
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 2.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->__construct( unserialize( $serialized ) );
	}

	/**
	 * @since 4.4
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->references );
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
		return $hasher->hash( $this->references );
	}

	/**
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

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->references );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * @since 5.0
	 *
	 * @return Iterator|Reference[]
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( array_values( $this->references ) );
	}

}
