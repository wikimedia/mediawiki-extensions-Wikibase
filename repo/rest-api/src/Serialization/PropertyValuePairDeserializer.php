<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use DataValues\DataValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\TimeValue;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\DataTypeValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairDeserializer {

	private EntityIdParser $entityIdParser;
	private PropertyDataTypeLookup $dataTypeLookup;
	private array $dataTypeToValueTypeMap;
	private DataValueDeserializer $dataValueDeserializer;
	private DataTypeValidatorFactory $dataTypeValidatorFactory;

	public function __construct(
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $dataTypeLookup,
		array $dataTypeToValueTypeMap,
		DataValueDeserializer $dataValueDeserializer,
		DataTypeValidatorFactory $dataTypeValidatorFactory
	) {
		$this->entityIdParser = $entityIdParser;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeToValueTypeMap = $dataTypeToValueTypeMap;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->dataTypeValidatorFactory = $dataTypeValidatorFactory;
	}

	public function deserialize( array $serialization ): Snak {
		$this->validateSerialization( $serialization );

		$propertyId = $this->parsePropertyId( $serialization['property']['id'] );

		try {
			$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( \Exception $e ) {
			throw new InvalidFieldException();
		}

		switch ( $serialization['value']['type'] ) {
			case 'novalue':
				return new PropertyNoValueSnak( $propertyId );
			case 'somevalue':
				return new PropertySomeValueSnak( $propertyId );
			case 'value':
				$dataValue = $this->deserializeValue( $dataTypeId, $serialization['value'] );
				return new PropertyValueSnak( $propertyId, $dataValue );
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

	private function deserializeValue( string $dataTypeId, array $valueSerialization ): DataValue {
		if ( !array_key_exists( 'content', $valueSerialization ) ) {
			throw new MissingFieldException();
		}

		$dataValueType = $this->dataTypeToValueTypeMap[$dataTypeId];
		switch ( $dataValueType ) {
			case 'wikibase-entityid':
				$dataValue = $this->deserializeEntityIdValue( $valueSerialization['content'] );
				break;
			case 'time':
				$dataValue = $this->deserializeTimeValue( $valueSerialization['content'] );
				break;
			default:
				try {
					$dataValue = $this->dataValueDeserializer->deserialize( [
						'type' => $dataValueType,
						'value' => $valueSerialization['content'],
					] );
				} catch ( DeserializationException $e ) {
					throw new InvalidFieldException();
				}
				break;
		}

		foreach ( $this->dataTypeValidatorFactory->getValidators( $dataTypeId ) as $validator ) {
			$result = $validator->validate( $dataValue );
			if ( !$result->isValid() ) {
				$error = $result->getErrors()[0];
				throw new InvalidFieldException( "[{$error->getCode()}] {$error->getText()}" );
			}
		}

		return $dataValue;
	}

	/**
	 * @param mixed $content
	 */
	private function deserializeEntityIdValue( $content ): EntityIdValue {
		try {
			$entityId = $this->entityIdParser->parse( $content );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidFieldException();
		}
		return new EntityIdValue( $entityId );
	}

	/**
	 * @param mixed $content
	 */
	private function deserializeTimeValue( $content ): TimeValue {
		try {
			return $this->newTimeValue(
				$content['time'],
				$content['precision'],
				$content['calendarmodel']
			);
		} catch ( \Exception $e ) {
			throw new InvalidFieldException();
		}
	}

	private function newTimeValue( string $timestamp, int $precision, string $calendarmodel ): TimeValue {
		return new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarmodel );
	}

}
