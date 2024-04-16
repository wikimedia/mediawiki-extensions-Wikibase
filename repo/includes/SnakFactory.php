<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use DataValues\DataValue;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Deserializers\SnakValueParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;

/**
 * Factory for creating new snaks.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SnakFactory {

	private PropertyDataTypeLookup $dataTypeLookup;
	private DataTypeFactory $dataTypeFactory;
	private SnakValueParser $snakValueParser;

	public function __construct(
	PropertyDataTypeLookup $dataTypeLookup,
	DataTypeFactory $dataTypeFactory,
	SnakValueParser $snakValueParser
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->snakValueParser = $snakValueParser;
	}

	/**
	 * Builds and returns a new snak from the provided property, snak type and optional snak value.
	 *
	 * @throws PropertyDataTypeLookupException from getDataTypeIdForProperty
	 * @throws OutOfBoundsException from getType
	 * @throws InvalidArgumentException from newDataValue, newDataValue and newSnak
	 */
	public function newSnak( PropertyId $propertyId, string $snakType, $rawValue = null ): Snak {
		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );

		switch ( $snakType ) {
			case 'novalue':
				return new PropertyNoValueSnak( $propertyId );
			case 'somevalue':
				return new PropertySomeValueSnak( $propertyId );
			case 'value':
				return new PropertyValueSnak( $propertyId, $this->parseValue( $dataType, $rawValue ) );
			default:
				throw new InvalidArgumentException( "bad snak type: $snakType" );
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function parseValue( DataType $dataType, $rawValue ): DataValue {
		if ( $rawValue === null ) {
			throw new InvalidArgumentException( "value snaks require the "
				. "'value' parameter to be set!" );
		}

		try {
			return $this->snakValueParser->parse(
				$dataType->getId(),
				[ 'type' => $dataType->getDataValueType(), 'value' => $rawValue ]
			);
		} catch ( DeserializationException $ex ) {
			throw new InvalidArgumentException( $ex->getMessage(), 0, $ex );
		}
	}

}
