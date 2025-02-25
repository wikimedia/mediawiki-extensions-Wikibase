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
	private array $deserializerBuilders;
	private array $dataTypeToValueTypeMap;
	private SnakValueDeserializer $snakValueDeserializer;

	public function __construct(
		EntityIdParser $propertyIdParser,
		Deserializer $dataValueDeserializer,
		PropertyDataTypeLookup $dataTypeLookup,
		array $deserializerBuilders,
		array $dataTypeToValueTypeMap,
		SnakValueDeserializer $snakValueDeserializer
	) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->propertyIdParser = $propertyIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->deserializerBuilders = $deserializerBuilders;
		$this->dataTypeToValueTypeMap = $dataTypeToValueTypeMap;
		$this->snakValueDeserializer = $snakValueDeserializer;
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

		$this->assertValidDataValue( $serialization['datavalue'] );

		return new PropertyValueSnak(
			$propertyId,
			$this->deserializeDataValue( $propertyId, $serialization['datavalue'] )
		);
	}

	private function deserializeDataValue( PropertyId $propertyId, array $serialization ): DataValue {
		try {
			return $this->needsDataTypeLookup( $serialization[DataValueDeserializer::TYPE_KEY] )
				? $this->lookUpDataTypeAndDeserializeValue( $propertyId, $serialization )
				: $this->dataValueDeserializer->deserialize( $serialization );
		} catch ( DeserializationException $ex ) {
			return $this->newUndeserializableValue( $serialization, $ex );
		}
	}

	private function assertValidDataValue( $serialization ): void {
		if ( !is_array( $serialization ) || !array_key_exists( DataValueDeserializer::TYPE_KEY, $serialization ) ) {
			throw new MissingTypeException( 'Not an array or missing the key "' . DataValueDeserializer::TYPE_KEY . '"' );
		}

		if ( !array_key_exists( DataValueDeserializer::VALUE_KEY, $serialization ) ) {
			throw new MissingAttributeException( DataValueDeserializer::VALUE_KEY );
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

		return array_intersect( $possibleDataTypeKeys, array_keys( $this->deserializerBuilders ) ) !== [];
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

	private function lookUpDataTypeAndDeserializeValue( PropertyId $propertyId, array $serialization ): DataValue {
		try {
			$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( Exception $e ) {
			return $this->newUndeserializableValue( $serialization, $e );
		}

		return $this->snakValueDeserializer->deserialize( $dataType, $serialization );
	}

}
