<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Exception;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

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
	public function deserialize( array $serialization, string $basePath = '' ): Snak {
		$this->validateSerialization( $serialization, $basePath );

		$propertyId = $this->parsePropertyId( $serialization['property']['id'], "$basePath/property" );

		try {
			$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( Exception $e ) {
			throw new InvalidFieldException( 'id', $serialization['property']['id'], "$basePath/property/id" );
		}

		switch ( $serialization['value']['type'] ) {
			case Value::TYPE_NO_VALUE:
				return new PropertyNoValueSnak( $propertyId );
			case Value::TYPE_SOME_VALUE:
				return new PropertySomeValueSnak( $propertyId );
			case Value::TYPE_VALUE:
				$dataValue = $this->valueDeserializer->deserialize( $dataTypeId, $serialization['value'], "$basePath/value" );
				return new PropertyValueSnak( $propertyId, $dataValue );
			default:
				throw new InvalidFieldException( 'type', $serialization['value']['type'], "$basePath/value/type" );
		}
	}

	private function validateSerialization( array $serialization, string $basePath ): void {
		$this->assertFieldExists( $serialization, 'property', $basePath );
		$this->assertFieldIsArray( $serialization, 'property', $basePath );
		$this->assertFieldExists( $serialization['property'], 'id', "$basePath/property" );
		$this->assertFieldIsString( $serialization['property'], 'id', "$basePath/property" );

		$this->assertFieldExists( $serialization, 'value', $basePath );
		$this->assertFieldIsArray( $serialization, 'value', $basePath );
		$this->assertFieldExists( $serialization['value'], 'type', "$basePath/value" );
		$this->assertFieldIsString( $serialization['value'], 'type', "$basePath/value" );
	}

	private function parsePropertyId( string $id, string $basePath ): PropertyId {
		try {
			$propertyId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidFieldException( 'id', $id, "$basePath/id" );
		}

		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new InvalidFieldException( 'id', $id, "$basePath/id" );
		}

		return $propertyId;
	}

	private function assertFieldExists( array $serialization, string $field, string $basePath ): void {
		if ( !array_key_exists( $field, $serialization ) ) {
			throw new MissingFieldException( $field, $basePath );
		}
	}

	private function assertFieldIsArray( array $serializationPart, string $field, string $basePath ): void {
		if ( !is_array( $serializationPart[$field] ) ) {
			throw new InvalidFieldException( $field, $serializationPart[$field], "$basePath/$field" );
		}
	}

	private function assertFieldIsString( array $serializationPart, string $field, string $basePath ): void {
		if ( !is_string( $serializationPart[$field] ) ) {
			throw new InvalidFieldException( $field, $serializationPart[$field], "$basePath/$field" );
		}
	}

}
