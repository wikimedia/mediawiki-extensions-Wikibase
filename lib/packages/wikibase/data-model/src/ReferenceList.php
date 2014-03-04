<?php

namespace Wikibase\DataModel;

use Hashable;
use InvalidArgumentException;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Implementation of the References interface.
 * @see References
 *
 * Note that this implementation is based on SplObjectStorage and
 * is not enforcing the type of objects set via it's native methods.
 * Therefore one can add non-Reference-implementing objects when
 * not sticking to the methods of the References interface.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
class ReferenceList extends HashableObjectStorage implements References {

	/**
	 * @see References::addReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function addReference( Reference $reference, $index = null ) {
		if( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( 'Index needs to be an integer value' );
		} else if ( is_null( $index ) || $index >= count( $this ) ) {
			// Append object to the end of the reference list.
			$this->attach( $reference );
		} else {
			$this->insertReferenceAtIndex( $reference, $index );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param Reference $reference
	 * @param int $index
	 */
	protected function insertReferenceAtIndex( Reference $reference, $index ) {
		$referencesToShift = array();
		$i = 0;

		// Determine the references that need to be shifted and detach them:
		foreach( $this as $object ) {
			if( $i++ >= $index ) {
				$referencesToShift[] = $object;
			}
		}

		foreach( $referencesToShift as $object ) {
			$this->detach( $object );
		}

		// Attach the new reference and reattach the previously detached references:
		$this->attach( $reference );

		foreach( $referencesToShift as $object ) {
			$this->attach( $object );
		}
	}

	/**
	 * @see References::hasReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference ) {
		return $this->contains( $reference )
			|| $this->hasReferenceHash( $reference->getHash() );
	}

	/**
	 * @see References::indexOf
	 *
	 * @since 0.5
	 *
	 * @param Reference $reference
	 *
	 * @return int|boolean
	 */
	public function indexOf( Reference $reference ) {
		$index = 0;

		foreach( $this as $object ) {
			if( $object === $reference ) {
				return $index;
			}
			$index++;
		}

		return false;
	}

	/**
	 * @see References::removeReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference ) {
		$this->removeReferenceHash( $reference->getHash() );
	}

	/**
	 * @see References::hasReferenceHash
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
	 * @see References::removeReferenceHash
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash	`
	 */
	public function removeReferenceHash( $referenceHash ) {
		$reference = $this->getReference( $referenceHash );

		if ( $reference !== null ) {
			$this->detach( $reference );
		}
	}

	/**
	 * @see References::getReference
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|null
	 */
	public function getReference( $referenceHash ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( $this as $hashable ) {
			if ( $hashable->getHash() === $referenceHash ) {
				return $hashable;
			}
		}

		return null;
	}

	/**
	 * @see References::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$references = array();

		/**
		 * @var Reference $reference
		 */
		foreach ( $this as $reference ) {
			$references[] = $reference->getSnaks()->toArray();
		}

		return $references;
	}

	/**
	 * Factory for constructing a ReferenceList from its array representation.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return References
	 */
	public static function newFromArray( array $data ) {
		$references = array();

		foreach ( $data as $snaks ) {
			$references[] = new Reference( SnakList::newFromArray( $snaks ) );
		}

		return new static( $references );
	}

}
