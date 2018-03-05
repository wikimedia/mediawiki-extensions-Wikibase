<?php

namespace Wikibase\Repo;

use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValue;
use DataValues\DataValueFactory;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Factory for creating new snaks.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SnakFactory {

	private $dataTypeLookup;
	private $dataTypeFactory;
	private $dataValueFactory;

	public function __construct(
		PropertyDataTypeLookup $dataTypeLookup,
		DataTypeFactory $dataTypeFactory,
		DataValueFactory $dataValueFactory
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->dataValueFactory = $dataValueFactory;
	}

	/**
	 * Builds and returns a new snak from the provided property, snak type and optional snak value.
	 *
	 * @param PropertyId $propertyId
	 * @param string $snakType
	 * @param mixed $rawValue
	 *
	 * @return Snak
	 * @throws PropertyDataTypeLookupException from getDataTypeIdForProperty
	 * @throws OutOfBoundsException from getType
	 * @throws InvalidArgumentException from newDataValue, newDataValue and newSnak
	 */
	public function newSnak( PropertyId $propertyId, $snakType, $rawValue = null ) {
		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );
		$valueType = $dataType->getDataValueType();

		$snakValue = $snakType !== 'value' ? null :
			$this->dataValueFactory->newDataValue( $valueType, $rawValue );

		$snak = $this->createSnak(
			$propertyId,
			$snakType,
			$snakValue
		);

		return $snak;
	}

	/**
	 * Builds and returns a new snak from the provided property, snak type
	 * and optional snak value and value type.
	 *
	 * @param PropertyId $propertyId
	 * @param string $snakType
	 * @param DataValue|null $value
	 *
	 * @return Snak
	 * @throws InvalidArgumentException
	 */
	private function createSnak( PropertyId $propertyId, $snakType, DataValue $value = null ) {
		switch ( $snakType ) {
			case 'value':
				if ( $value === null ) {
					throw new InvalidArgumentException( "value snaks require the "
						. "'value' parameter to be set!" );
				}

				$snak = new PropertyValueSnak( $propertyId, $value );
				break;
			case 'novalue':
				$snak = new PropertyNoValueSnak( $propertyId );
				break;
			case 'somevalue':
				$snak = new PropertySomeValueSnak( $propertyId );
				break;
			default:
				throw new InvalidArgumentException( "bad snak type: $snakType" );
		}

		return $snak;
	}

}
