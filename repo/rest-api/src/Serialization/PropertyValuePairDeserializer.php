<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

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

	private EntityIdParser $entityIdParser;
	private PropertyDataTypeLookup $dataTypeLookup;
	private ValueDeserializer $valueDeserializer;

	public function __construct(
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $dataTypeLookup,
		ValueDeserializer $valueDeserializer
	) {
		$this->entityIdParser = $entityIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->valueDeserializer = $valueDeserializer;
	}

	/**
	 * @throws MissingFieldException
	 * @throws InvalidFieldException
	 */
	public function deserialize( array $serialization ): Snak {
		$this->validateSerialization( $serialization );

		$propertyId = $this->parsePropertyId( $serialization['property']['id'] );

		try {
			$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( \Exception $e ) {
			throw new InvalidFieldException( 'id', $serialization['property']['id'] );
		}

		switch ( $serialization['value']['type'] ) {
			case 'novalue':
				return new PropertyNoValueSnak( $propertyId );
			case 'somevalue':
				return new PropertySomeValueSnak( $propertyId );
			case 'value':
				$dataValue = $this->valueDeserializer->deserialize( $dataTypeId, $serialization['value'] );
				return new PropertyValueSnak( $propertyId, $dataValue );
			default: // should be unreachable because of prior validation
				throw new \LogicException( 'value type must be one of "value", "novalue", "somevalue".' );
		}
	}

	private function validateSerialization( array $serialization ): void {
		$this->assertFieldExists( $serialization, 'property' );
		$this->assertFieldIsArray( $serialization, 'property' );
		$this->validateProperty( $serialization['property'] );

		$this->assertFieldExists( $serialization, 'value' );
		$this->assertFieldIsArray( $serialization, 'value' );
		$this->validateValue( $serialization['value'] );
	}

	private function validateProperty( array $propertySerialization ): void {
		$this->assertFieldExists( $propertySerialization, 'id' );
	}

	private function validateValue( array $valueSerialization ): void {
		$this->assertFieldExists( $valueSerialization, 'type' );

		if ( !in_array( $valueSerialization['type'], [ 'value', 'novalue', 'somevalue' ], true ) ) {
			throw new InvalidFieldException( 'type', $valueSerialization['type'] );
		}
	}

	private function parsePropertyId( string $id ): PropertyId {
		try {
			$propertyId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidFieldException( 'id', $id );
		}

		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new InvalidFieldException( 'id', $id );
		}

		return $propertyId;
	}

	private function assertFieldExists( array $serialization, string $field ): void {
		if ( !array_key_exists( $field, $serialization ) ) {
			throw new MissingFieldException( $field );
		}
	}

	private function assertFieldIsArray( array $serializationPart, string $field ): void {
		if ( !is_array( $serializationPart[$field] ) ) {
			throw new InvalidFieldException( $field, $serializationPart[$field] );
		}
	}

}
