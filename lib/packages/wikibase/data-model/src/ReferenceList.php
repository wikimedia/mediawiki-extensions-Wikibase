<?php

namespace Wikibase\DataModel;

use ArrayIterator;
use Comparable;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Mättig
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferenceList implements Comparable, Countable, IteratorAggregate {

	/**
	 * @var Reference[]
	 */
	private $references = array();

	/**
	 * @param Reference[]|Traversable $references
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $references = array() ) {
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
	 * @param int|null $index
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

		if ( $index === null || $index >= count( $this->references ) ) {
			// Append object to the end of the reference list.
			$this->references[] = $reference;
		} else {
			$this->insertReferenceAtIndex( $reference, $index );
		}
	}

	/**
	 * @since 1.1
	 *
	 * @param Snak[]|Snak $snaks
	 * @param Snak [$snak2,...]
	 *
	 * @throws InvalidArgumentException
	 */
	public function addNewReference( $snaks = array() /*...*/ ) {
		if ( $snaks instanceof Snak ) {
			$snaks = func_get_args();
		}

		$this->addReference( new Reference( $snaks ) );
	}

	/**
	 * @param Reference $reference
	 * @param int $index
	 */
	private function insertReferenceAtIndex( Reference $reference, $index ) {
		array_splice( $this->references, $index, 0, array( $reference ) );
	}

	/**
	 * Returns if the list contains a reference with the same hash as the provided reference.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference ) {
		return $this->hasReferenceHash( $reference->getHash() );
	}

	/**
	 * Returns the index of a reference or false if the reference could not be found.
	 *
	 * @since 0.5
	 *
	 * @param Reference $reference
	 *
	 * @return int|boolean
	 */
	public function indexOf( Reference $reference ) {
		foreach ( $this->references as $index => $ref ) {
			if ( $ref->equals( $reference ) ) {
				return $index;
			}
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
	 * @return boolean
	 */
	public function hasReferenceHash( $referenceHash ) {
		return $this->getReference( $referenceHash ) !== null;
	}

	/**
	 * Removes the reference with the provided hash if it exists in the list.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash	`
	 */
	public function removeReferenceHash( $referenceHash ) {
		foreach ( $this->references as $index => $reference ) {
			if ( $reference->getHash() === $referenceHash ) {
				$this->removeReferenceAtIndex( $index );
			}
		}
	}

	/**
	 * @param int $index
	 */
	private function removeReferenceAtIndex( $index ) {
		array_splice( $this->references, $index, 1 );
	}

	/**
	 * Returns the reference with the provided hash, or
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
		return serialize( $this->references );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 2.1
	 *
	 * @param string $data
	 */
	public function unserialize( $data ) {
		$this->__construct( unserialize( $data ) );
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

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->references );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * @since 5.0
	 *
	 * @return Traversable
	 */
	public function getIterator() {
		return new ArrayIterator( $this->references );
	}

	/**
	 * @since 5.0
	 *
	 * @return Reference[]
	 */
	public function toArray() {
		return $this->references;
	}

}
