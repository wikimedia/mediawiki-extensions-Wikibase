<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Exception;
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
		} catch ( Exception $e ) {
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
			default:
				throw new InvalidFieldException( 'type', $serialization['value']['type'] );
		}
	}

	private function validateSerialization( array $serialization ): void {
		$this->assertFieldExists( $serialization, 'property' );
		$this->assertFieldIsArray( $serialization, 'property' );
		$this->assertFieldExists( $serialization['property'], 'id' );
		$this->assertFieldIsString( $serialization['property'], 'id' );

		$this->assertFieldExists( $serialization, 'value' );
		$this->assertFieldIsArray( $serialization, 'value' );
		$this->assertFieldExists( $serialization['value'], 'type' );
		$this->assertFieldIsString( $serialization['value'], 'type' );
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

	private function assertFieldIsString( array $serializationPart, string $field ): void {
		if ( !is_string( $serializationPart[$field] ) ) {
			throw new InvalidFieldException( $field, $serializationPart[$field] );
		}
	}

}
