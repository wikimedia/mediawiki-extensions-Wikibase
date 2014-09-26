<?php

namespace Wikibase\DataModel;

use OutOfBoundsException;
use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Helper for managing objects indexed by property id.
 *
 * This is a light weight alternative approach to using something
 * like GenericArrayObject with the advantages that no extra interface
 * is needed and that indexing does not happen automatically.
 *
 * Lack of automatic indexing means that you will need to call the
 * buildIndex method before doing any look-ups.
 *
 * Since no extra interface is used, the user is responsible for only
 * adding objects that have a getPropertyId method that returns either
 * a string or integer when called with no arguments.
 *
 * Objects may be added or moved within the structure. Absolute indices (indices according to the
 * flat list of objects) may be specified to add or move objects. These management operations take
 * the property grouping into account. Adding or moving objects outside their "property groups"
 * shifts the whole group towards that index.
 *
 * Example of moving an object within its "property group":
 * o1 (p1)                           o1 (p1)
 * o2 (p2)                       /-> o3 (p2)
 * o3 (p2) ---> move to index 1 -/   o2 (p2)
 *
 * Example of moving an object that triggers moving the whole "property group":
 * o1 (p1)                       /-> o3 (p2)
 * o2 (p2)                       |   o2 (p2)
 * o3 (p2) ---> move to index 0 -/   o1 (p1)
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class ByPropertyIdArray extends \ArrayObject {

	/**
	 * @since 0.2
	 *
	 * @var array[]|null
	 */
	private $byId = null;

	/**
	 * @see \ArrayObject::__construct
	 *
	 * @param array|object $input
	 */
	public function __construct( $input = null ) {
		parent::__construct( (array)$input );
	}

	/**
	 * Builds the index for doing look-ups by property id.
	 *
	 * @since 0.2
	 */
	public function buildIndex() {
		$this->byId = array();

		foreach ( $this as $object ) {
			$propertyId = $object->getPropertyId()->getSerialization();

			if ( !array_key_exists( $propertyId, $this->byId ) ) {
				$this->byId[$propertyId] = array();
			}

			$this->byId[$propertyId][] = $object;
		}
	}

	/**
	 * Checks whether id indexed array has been generated.
	 * @since 0.5
	 *
	 * @throws RuntimeException
	 */
	private function assertIndexIsBuild() {
		if ( $this->byId === null ) {
			throw new RuntimeException( 'Index not build, call buildIndex first' );
		}
	}

	/**
	 * Returns the property ids used for indexing.
	 *
	 * @since 0.2
	 *
	 * @return PropertyId[]
	 * @throws RuntimeException
	 */
	public function getPropertyIds() {
		$this->assertIndexIsBuild();

		return array_map(
			function( $serializedPropertyId ) {
				return new PropertyId( $serializedPropertyId );
			},
			array_keys( $this->byId )
		);
	}

	/**
	 * Returns the objects featuring the provided property id in the index.
	 *
	 * @since 0.2
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return object[]
	 * @throws RuntimeException|OutOfBoundsException
	 */
	public function getByPropertyId( PropertyId $propertyId ) {
		$this->assertIndexIsBuild();

		if ( !( array_key_exists( $propertyId->getSerialization(), $this->byId ) ) ) {
			throw new OutOfBoundsException( 'Property id array key does not exist.' );
		}

		return $this->byId[$propertyId->getSerialization()];
	}

	/**
	 * Returns the absolute index of an object or false if the object could not be found.
	 * @since 0.5
	 *
	 * @param object $object
	 * @return bool|int
	 *
	 * @throws RuntimeException
	 */
	public function getFlatArrayIndexOfObject( $object ) {
		$this->assertIndexIsBuild();

		$i = 0;
		foreach( $this as $o ) {
			if( $o === $object ) {
				return $i;
			}
			$i++;
		}
		return false;
	}

	/**
	 * Returns the objects in a flat array (using the indexed form for generating the array).
	 * @since 0.5
	 *
	 * @return object[]
	 *
	 * @throws RuntimeException
	 */
	public function toFlatArray() {
		$this->assertIndexIsBuild();

		$array = array();
		foreach( $this->byId as $objects ) {
			$array = array_merge( $array, $objects );
		}
		return $array;
	}

	/**
	 * Returns the absolute numeric indices of objects featuring the same property id.
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 * @return int[]
	 *
	 * @throws RuntimeException
	 */
	private function getFlatArrayIndices( PropertyId $propertyId ) {
		$this->assertIndexIsBuild();

		$propertyIndices = array();
		$i = 0;

		foreach( $this->byId as $serializedPropertyId => $objects ) {
			if( $serializedPropertyId === $propertyId->getSerialization() ) {
				$propertyIndices = range( $i, $i + count( $objects ) - 1 );
				break;
			} else {
				$i += count( $objects );
			}
		}

		return $propertyIndices;
	}

	/**
	 * Moves an object within its "property group".
	 * @since 0.5
	 *
	 * @param object $object
	 * @param int $toIndex Absolute index within a "property group".
	 *
	 * @throws OutOfBoundsException
	 */
	private function moveObjectInPropertyGroup( $object, $toIndex ) {
		$currentIndex = $this->getFlatArrayIndexOfObject( $object );

		if( $toIndex === $currentIndex ) {
			return;
		}

		$propertyId = $object->getPropertyId();

		$numericIndices = $this->getFlatArrayIndices( $propertyId );
		$lastIndex = $numericIndices[count( $numericIndices ) - 1];

		if( $toIndex > $lastIndex + 1 || $toIndex < $numericIndices[0] ) {
			throw new OutOfBoundsException( 'Object cannot be moved to ' . $toIndex );
		}

		if( $toIndex >= $lastIndex ) {
			$this->moveObjectToEndOfPropertyGroup( $object );
		} else {
			$this->removeObject( $object );

			$propertyGroup = array_combine(
				$this->getFlatArrayIndices( $propertyId ),
				$this->getByPropertyId( $propertyId )
			);

			$insertBefore = $propertyGroup[$toIndex];
			$this->insertObjectAtIndex( $object, $this->getFlatArrayIndexOfObject( $insertBefore ) );
		}
	}

	/**
	 * Moves an object to the end of its "property group".
	 * @since 0.5
	 *
	 * @param object $object
	 */
	private function moveObjectToEndOfPropertyGroup( $object ) {
		$this->removeObject( $object );

		/** @var PropertyId $propertyId */
		$propertyId = $object->getPropertyId();
		$propertyIdSerialization = $propertyId->getSerialization();

		$propertyGroup = in_array( $propertyIdSerialization, $this->getPropertyIds() )
			? $this->getByPropertyId( $propertyId )
			: array();

		$propertyGroup[] = $object;
		$this->byId[$propertyIdSerialization] = $propertyGroup;

		$this->exchangeArray( $this->toFlatArray() );
	}

	/**
	 * Removes an object from the array structures.
	 * @since 0.5
	 *
	 * @param object $object
	 */
	private function removeObject( $object ) {
		$flatArray = $this->toFlatArray();
		$this->exchangeArray( $flatArray );
		$this->offsetUnset( array_search( $object, $flatArray ) );
		$this->buildIndex();
	}

	/**
	 * Inserts an object at a specific index.
	 * @since 0.5
	 *
	 * @param object $object
	 * @param int $index Absolute index within the flat list of objects.
	 */
	private function insertObjectAtIndex( $object, $index ) {
		$flatArray = $this->toFlatArray();

		$this->exchangeArray( array_merge(
			array_slice( $flatArray, 0, $index ),
			array( $object ),
			array_slice( $flatArray, $index )
		) );

		$this->buildIndex();
	}

	/**
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 * @param int $toIndex
	 */
	private function movePropertyGroup( PropertyId $propertyId, $toIndex ) {
		if( $this->getPropertyGroupIndex( $propertyId ) === $toIndex ) {
			return;
		}

		/**
		 * @var PropertyId
		 */
		$insertBefore = null;

		$oldIndex = $this->getPropertyGroupIndex( $propertyId );
		$byIdClone = $this->byId;

		// Remove "property group" to calculate the groups new index:
		unset( $this->byId[$propertyId->getSerialization()] );

		if( $toIndex > $oldIndex ) {
			// If the group shall be moved towards the bottom, the number of objects within the
			// group needs to be subtracted from the absolute toIndex:
			$toIndex -= count( $byIdClone[$propertyId->getSerialization()] );
		}

		foreach( $this->getPropertyIds() as $pId ) {
			// Accepting other than the exact index by using <= letting the "property group" "latch"
			// in the next slot.
			if( $toIndex <= $this->getPropertyGroupIndex( $pId ) ) {
				$insertBefore = $pId;
				break;
			}
		}

		$serializedPropertyId = $propertyId->getSerialization();
		$this->byId = array();

		foreach( $byIdClone as $serializedPId => $objects ) {
			$pId = new PropertyId( $serializedPId );
			if( $pId->equals( $propertyId ) ) {
				continue;
			} elseif( $pId->equals( $insertBefore ) ) {
				$this->byId[$serializedPropertyId] = $byIdClone[$serializedPropertyId];
			}
			$this->byId[$serializedPId] = $objects;
		}

		if( is_null( $insertBefore ) ) {
			$this->byId[$serializedPropertyId] = $byIdClone[$serializedPropertyId];
		}

		$this->exchangeArray( $this->toFlatArray() );
	}

	/**
	 * Returns the index of a "property group" (the first object in the flat array that features
	 * the specified property). Returns false if property id could not be found.
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 * @return bool|int
	 */
	private function getPropertyGroupIndex( PropertyId $propertyId ) {
		$i = 0;

		foreach( $this->byId as $serializedPropertyId => $objects ) {
			$pId = new PropertyId( $serializedPropertyId );
			if( $pId->equals( $propertyId ) ) {
				return $i;
			}
			$i += count( $objects );
		}

		return false;
	}

	/**
	 * Moves an existing object to a new index. Specifying an index outside the object's "property
	 * group" will move the object to the edge of the "property group" and shift the whole group
	 * to achieve the designated index for the object to move.
	 * @since 0.5
	 *
	 * @param object $object
	 * @param int $toIndex Absolute index where to move the object to.
	 *
	 * @throws RuntimeException|OutOfBoundsException
	 */
	public function moveObjectToIndex( $object, $toIndex ) {
		$this->assertIndexIsBuild();

		if( !in_array( $object, $this->toFlatArray() ) ) {
			throw new OutOfBoundsException( 'Object not present in array' );
		} elseif( $toIndex < 0 || $toIndex > count( $this ) ) {
			throw new OutOfBoundsException( 'Specified index is out of bounds' );
		} elseif( $this->getFlatArrayIndexOfObject( $object ) === $toIndex ) {
			return;
		}

		// Determine whether to simply reindex the object within its "property group":
		$propertyIndices = $this->getFlatArrayIndices( $object->getPropertyId() );

		if( in_array( $toIndex, $propertyIndices ) ) {
			$this->moveObjectInPropertyGroup( $object, $toIndex );
		} else {
			$edgeIndex = ( $toIndex <= $propertyIndices[0] )
				? $propertyIndices[0]
				: $propertyIndices[count( $propertyIndices ) - 1];

			$this->moveObjectInPropertyGroup( $object, $edgeIndex );
			$this->movePropertyGroup( $object->getPropertyId(), $toIndex );
		}

		$this->exchangeArray( $this->toFlatArray() );
	}

	/**
	 * Adds an object at a specific index. If no index is specified, the object will be append to
	 * the end of its "property group" or - if no objects featuring the same property exist - to the
	 * absolute end of the array.
	 * Specifying an index outside a "property group" will place the new object at the specified
	 * index with the existing "property group" objects being shifted towards the new new object.
	 * @since 0.5
	 *
	 * @param object $object
	 * @param int $index Absolute index where to place the new object.
	 *
	 * @throws RuntimeException
	 */
	public function addObjectAtIndex( $object, $index = null ) {
		$this->assertIndexIsBuild();

		$propertyId = $object->getPropertyId();
		$validIndices = $this->getFlatArrayIndices( $propertyId );

		if( count( $this ) === 0 ) {
			// Array is empty, just append object.
			$this->append( $object );

		} elseif( count( $validIndices ) === 0 ) {
			// No objects featuring that property exist. The object may be inserted at a place
			// between existing "property groups".
			$this->append( $object );
			if( !is_null( $index ) ) {
				$this->buildIndex();
				$this->moveObjectToIndex( $object, $index );
			}

		} else {
			// Objects featuring the same property as the object which is about to be added already
			// exist in the array.
			$this->addObjectToPropertyGroup( $object, $index );
		}

		$this->buildIndex();
	}

	/*
	 * Adds an object to an existing property group at the specified absolute index.
	 * @since 0.5
	 *
	 * @param object $object
	 * @param int $index
	 *
	 * @throws OutOfBoundsException
	 */
	private function addObjectToPropertyGroup( $object, $index = null ) {
		/** @var PropertyId $propertyId */
		$propertyId = $object->getPropertyId();
		$validIndices = $this->getFlatArrayIndices( $propertyId );

		if( count( $validIndices ) === 0 ) {
			throw new OutOfBoundsException( 'No objects featuring the object\'s property exist' );
		}

		// Add index to allow placing object after the last object of the "property group":
		$validIndices[] = $validIndices[count( $validIndices ) - 1] + 1;

		if( is_null( $index ) ) {
			// If index is null, append object to "property group".
			$index = $validIndices[count( $validIndices ) - 1];
		}

		if( in_array( $index, $validIndices ) ) {
			// Add object at index within "property group".
			$this->byId[$propertyId->getSerialization()][] = $object;
			$this->exchangeArray( $this->toFlatArray() );
			$this->moveObjectToIndex( $object, $index );

		} else {
			// Index is out of the "property group"; The whole group needs to be moved.
			$this->movePropertyGroup( $propertyId, $index );

			// Move new object to the edge of the "property group" to receive its designated
			// index:
			if( $index < $validIndices[0] ) {
				array_unshift( $this->byId[$propertyId->getSerialization()], $object );
			} else {
				$this->byId[$propertyId->getSerialization()][] = $object;
			}
		}

		$this->exchangeArray( $this->toFlatArray() );
	}

}
