<?php

namespace Wikibase\DataModel\Services;

use InvalidArgumentException;
use OutOfBoundsException;
use Traversable;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\PropertyIdProvider;

/**
 * Groups property id providers by their property id.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ByPropertyIdGrouper {

	/**
	 * @var array[]
	 */
	private $byPropertyId = [];

	/**
	 * @param PropertyIdProvider[]|Traversable $propertyIdProviders
	 *
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
				throw new InvalidArgumentException(
					'Every element in $propertyIdProviders must be an instance of PropertyIdProvider'
				);
			}
		}
	}

	/**
	 * @param PropertyIdProvider[]|Traversable $propertyIdProviders
	 */
	private function indexPropertyIdProviders( $propertyIdProviders ) {
		foreach ( $propertyIdProviders as $propertyIdProvider ) {
			$this->addPropertyIdProvider( $propertyIdProvider );
		}
	}

	private function addPropertyIdProvider( PropertyIdProvider $propertyIdProvider ) {
		$idSerialization = $propertyIdProvider->getPropertyId()->getSerialization();
		$this->byPropertyId[$idSerialization][] = $propertyIdProvider;
	}

	/**
	 * Returns all property ids which were found.
	 *
	 * @since 1.0
	 *
	 * @return NumericPropertyId[]
	 */
	public function getPropertyIds() {
		return array_map(
			static function( $propertyId ) {
				return new NumericPropertyId( $propertyId );
			},
			array_keys( $this->byPropertyId )
		);
	}

	/**
	 * Returns the PropertyIdProvider instances for the given NumericPropertyId.
	 *
	 * @since 1.0
	 *
	 * @param NumericPropertyId $propertyId
	 *
	 * @throws OutOfBoundsException
	 * @return PropertyIdProvider[]
	 */
	public function getByPropertyId( NumericPropertyId $propertyId ) {
		$idSerialization = $propertyId->getSerialization();

		if ( !isset( $this->byPropertyId[$idSerialization] ) ) {
			throw new OutOfBoundsException( 'PropertyIdProvider with propertyId "' . $idSerialization . '" not found' );
		}

		return $this->byPropertyId[$idSerialization];
	}

	/**
	 * Checks if there are PropertyIdProvider instances for the given NumericPropertyId.
	 *
	 * @since 1.0
	 *
	 * @param NumericPropertyId $propertyId
	 *
	 * @return bool
	 */
	public function hasPropertyId( NumericPropertyId $propertyId ) {
		return isset( $this->byPropertyId[$propertyId->getSerialization()] );
	}

}
