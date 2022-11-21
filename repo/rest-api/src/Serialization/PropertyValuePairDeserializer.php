<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairDeserializer {

	private PropertyDataTypeLookup $dataTypeLookup;
	private array $dataTypeToValueTypeMap;
	private DataValueDeserializer $dataValueDeserializer;
	private EntityIdParser $entityIdParser;

	public function __construct(
		PropertyDataTypeLookup $dataTypeLookup,
		array $dataTypeToValueTypeMap,
		DataValueDeserializer $dataValueDeserializer,
		EntityIdParser $entityIdParser
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeToValueTypeMap = $dataTypeToValueTypeMap;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdParser = $entityIdParser;
	}

	public function deserialize( array $serialization ): Snak {
		$this->validateSerialization( $serialization );

		$propertyId = $this->parsePropertyId( $serialization['property']['id'] );

		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( \Exception $e ) {
			throw new InvalidFieldException();
		}

		switch ( $serialization['value']['type'] ) {
			case 'novalue':
				return new PropertyNoValueSnak( $propertyId );
			case 'somevalue':
				return new PropertySomeValueSnak( $propertyId );
			case 'value':
				if ( !array_key_exists( 'content', $serialization['value'] ) ) {
					throw new MissingFieldException();
				}

				try {
					$value = $this->dataValueDeserializer->deserialize( [
						'type' => $this->dataTypeToValueTypeMap[$dataType],
						'value' => $serialization['value']['content'],
					] );
				} catch ( DeserializationException $e ) {
					throw new InvalidFieldException();
				}

				return new PropertyValueSnak( $propertyId, $value );
		}
	}

	private function validateSerialization( array $serialization ): void {
		if ( !array_key_exists( 'value', $serialization )
			|| !array_key_exists( 'property', $serialization )
		) {
			throw new MissingFieldException();
		}

		if ( !is_array( $serialization['property'] ) || !is_array( $serialization['value'] ) ) {
			throw new InvalidFieldException();
		}

		$this->validateProperty( $serialization['property'] );
		$this->validateValue( $serialization['value'] );
	}

	private function validateProperty( array $propertySerialization ): void {
		if ( !array_key_exists( 'id', $propertySerialization ) ) {
			throw new MissingFieldException();
		}
	}

	private function validateValue( array $valueSerialization ): void {
		if ( !array_key_exists( 'type', $valueSerialization ) ) {
			throw new MissingFieldException();
		}

		if ( !in_array( $valueSerialization['type'], [ 'value', 'novalue', 'somevalue' ], true ) ) {
			throw new InvalidFieldException();
		}
	}

	private function parsePropertyId( string $id ): PropertyId {
		try {
			$propertyId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidFieldException();
		}

		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new InvalidFieldException();
		}

		return $propertyId;
	}

}
