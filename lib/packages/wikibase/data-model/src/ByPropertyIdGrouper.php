<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;
use OutOfBoundsException;
use Traversable;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Groups property id providers by their property id.
 *
 * @since 1.1
 *
 * @license GNU GpL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ByPropertyIdGrouper {

	/**
	 * @var PropertyIdProvider[][]
	 */
	private $byPropertyId = array();

	/**
	 * @param PropertyIdProvider[] $propertyIdProviders
	 * @throws InvalidArgumentException
	 */
	public function __construct( $propertyIdProviders ) {
		$this->assertArePropertyIdProviders( $propertyIdProviders );
		$this->indexPropertyIdProviders( $propertyIdProviders );
	}

	private function assertArePropertyIdProviders( $propertyIdProviders ) {
		if ( !is_array( $propertyIdProviders ) && !( $propertyIdProviders instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$propertyIdProviders must be an array or an instance of Traversable' );
		}

		foreach ( $propertyIdProviders as $propertyIdProvider ) {
			if ( !( $propertyIdProvider instanceof PropertyIdProvider ) ) {
				throw new InvalidArgumentException( 'Every element in $propertyIdProviders must be an instance of PropertyIdProvider' );
			}
		}
	}

	private function indexPropertyIdProviders( $propertyIdProviders ) {
		foreach ( $propertyIdProviders as $propertyIdProvider ) {
			$this->addPropertyIdProvider( $propertyIdProvider );
		}
	}

	private function addPropertyIdProvider( PropertyIdProvider $propertyIdProvider ) {
		$idSerialization = $propertyIdProvider->getPropertyId()->getSerialization();

		if ( isset( $this->byPropertyId[$idSerialization] ) ) {
			$this->byPropertyId[$idSerialization][] = $propertyIdProvider;
		} else {
			$this->byPropertyId[$idSerialization] = array( $propertyIdProvider );
		}
	}

	/**
	 * Returns all property ids which were found.
	 *
	 * @since 1.1
	 *
	 * @return PropertyId[]
	 */
	public function getPropertyIds() {
		$propertyIds = array_keys( $this->byPropertyId );

		array_walk( $propertyIds, function( &$propertyId ) {
			$propertyId = new PropertyId( $propertyId );
		} );

		return $propertyIds;
	}

	/**
	 * Returns the PropertyIdProvider instances for the given PropertyId.
	 *
	 * @since 1.1
	 *
	 * @param PropertyId $propertyId
	 * @return PropertyIdProvider[]
	 * @throws OutOfBoundsException
	 */
	public function getByPropertyId( PropertyId $propertyId ) {
		$idSerialization = $propertyId->getSerialization();

		if ( !isset( $this->byPropertyId[$idSerialization] ) ) {
			throw new OutOfBoundsException( 'PropertyIdProvider with propertyId "' . $idSerialization . '" not found' );
		}

		return $this->byPropertyId[$idSerialization];
	}

	/**
	 * Checks if there are PropertyIdProvider instances for the given PropertyId.
	 *
	 * @since 1.1
	 *
	 * @param PropertyId $propertyId
	 * @return boolean
	 */
	public function hasPropertyId( PropertyId $propertyId ) {
		return isset( $this->byPropertyId[$propertyId->getSerialization()] );
	}

}
