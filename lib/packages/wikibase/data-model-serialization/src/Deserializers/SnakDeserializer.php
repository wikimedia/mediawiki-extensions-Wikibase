<?php

namespace Wikibase\DataModel\Deserializers;

use DataValues\DataValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Exception;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakObject;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 */
class SnakDeserializer implements DispatchableDeserializer {

	private Deserializer $dataValueDeserializer;
	private EntityIdParser $propertyIdParser;
	private PropertyDataTypeLookup $dataTypeLookup;
	private array $valueParserCallbacks;
	private array $dataTypeToValueTypeMap;
	private SnakValueParser $snakValueParser;

	public function __construct(
		EntityIdParser $propertyIdParser,
		Deserializer $dataValueDeserializer,
		PropertyDataTypeLookup $dataTypeLookup,
		array $valueParserCallbacks,
		array $dataTypeToValueTypeMap,
		SnakValueParser $snakValueParser
	) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->propertyIdParser = $propertyIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->valueParserCallbacks = $valueParserCallbacks;
		$this->dataTypeToValueTypeMap = $dataTypeToValueTypeMap;
		$this->snakValueParser = $snakValueParser;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ): bool {
		return is_array( $serialization )
			&& $this->hasSnakType( $serialization )
			&& $this->hasCorrectSnakType( $serialization );
	}

	private function hasSnakType( array $serialization ): bool {
		return array_key_exists( 'snaktype', $serialization );
	}

	private function hasCorrectSnakType( array $serialization ): bool {
		return in_array( $serialization['snaktype'], [ 'novalue', 'somevalue', 'value' ] );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return PropertyNoValueSnak|PropertySomeValueSnak|PropertyValueSnak
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		$this->requireAttribute( $serialization, 'property' );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @see SnakDeserializer::hasCorrectSnakType
	 *
	 * @param array $serialization
	 *
	 * @throws InvalidAttributeException
	 * @return PropertyNoValueSnak|PropertySomeValueSnak|PropertyValueSnak
	 */
	private function getDeserialized( array $serialization ): SnakObject {
		switch ( $serialization['snaktype'] ) {
			case 'novalue':
				return $this->newNoValueSnak( $serialization );
			case 'somevalue':
				return $this->newSomeValueSnak( $serialization );
			default:
				return $this->newValueSnak( $serialization );
		}
	}

	private function newNoValueSnak( array $serialization ): PropertyNoValueSnak {
		return new PropertyNoValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newSomeValueSnak( array $serialization ): PropertySomeValueSnak {
		return new PropertySomeValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newValueSnak( array $serialization ): PropertyValueSnak {
		$this->requireAttribute( $serialization, 'datavalue' );
		$propertyId = $this->deserializePropertyId( $serialization['property'] );

		return new PropertyValueSnak(
			$propertyId,
			$this->deserializeDataValue( $propertyId, $serialization['datavalue'] )
		);
	}

	private function deserializeDataValue( PropertyId $propertyId, array $serialization ): DataValue {
		try {
			return $this->needsDataTypeLookup( $serialization[DataValueDeserializer::TYPE_KEY] )
				? $this->lookUpDataTypeAndParseValue( $propertyId, $serialization )
				: $this->dataValueDeserializer->deserialize( $serialization );
		} catch ( DeserializationException $ex ) {
			return $this->newUndeserializableValue( $serialization, $ex );
		}
	}

	/**
	 * We only need to look up the data type if the value type needs a data type specific parser.
	 */
	private function needsDataTypeLookup( string $valueType ): bool {
		$possibleDataTypeKeys = array_map(
			fn( string $dataType ) => "PT:$dataType",
			array_keys( $this->dataTypeToValueTypeMap, $valueType, true )
		);

		return !empty( array_intersect( $possibleDataTypeKeys, array_keys( $this->valueParserCallbacks ) ) );
	}

	/**
	 * @throws InvalidAttributeException
	 */
	private function deserializePropertyId( string $serialization ): PropertyId {
		try {
			$id = $this->propertyIdParser->parse( $serialization );
		} catch ( EntityIdParsingException $ex ) {
			throw new InvalidAttributeException(
				'property',
				$serialization,
				"'$serialization' is not a valid property ID"
			);
		}

		if ( $id instanceof PropertyId ) {
			return $id;
		}

		throw new InvalidAttributeException(
			'property',
			$serialization,
			"'$serialization' is not a property ID"
		);
	}

	private function assertCanDeserialize( $serialization ): void {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The snak serialization should be an array' );
		}

		if ( !$this->hasSnakType( $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasCorrectSnakType( $serialization ) ) {
			throw new UnsupportedTypeException( $serialization['snaktype'] );
		}
	}

	private function requireAttribute( array $array, string $attributeName ): void {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
	}

	private function newUndeserializableValue( array $serialization, Exception $exception ): UnDeserializableValue {
		$value = $serialization[DataValueDeserializer::VALUE_KEY] ?? null;
		$type = $serialization[DataValueDeserializer::TYPE_KEY] ?? null;
		$error = $serialization['error'] ?? $exception->getMessage();

		return new UnDeserializableValue( $value, $type, $error );
	}

	private function lookUpDataTypeAndParseValue( PropertyId $propertyId, array $serialization ): DataValue {
		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( Exception $e ) {
			return $this->newUndeserializableValue( $serialization, $e );
		}

		return $this->snakValueParser->parse( $dataType, $serialization );
	}

}
