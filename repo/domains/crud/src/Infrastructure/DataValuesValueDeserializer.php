<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use DataValues\DataValue;
use DataValues\IllegalValueException;
use DataValues\TimeValue;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Deserializers\SnakValueDeserializer;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ValueDeserializer;
use Wikibase\Repo\Domains\Crud\Domain\Services\ValueTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class DataValuesValueDeserializer implements ValueDeserializer {

	private ValueTypeLookup $valueTypeLookup;
	private SnakValueDeserializer $snakValueDeserializer;
	private DataTypeValidatorFactory $validatorFactory;

	public function __construct(
		ValueTypeLookup $valueTypeLookup,
		SnakValueDeserializer $snakValueDeserializer,
		DataTypeValidatorFactory $validatorFactory
	) {
		$this->valueTypeLookup = $valueTypeLookup;
		$this->snakValueDeserializer = $snakValueDeserializer;
		$this->validatorFactory = $validatorFactory;
	}

	public function deserialize( string $dataTypeId, array $valueSerialization, string $basePath = '' ): DataValue {
		$this->assertFieldExists( $valueSerialization, 'content', $basePath );

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
		switch ( $dataValueType = $this->valueTypeLookup->getValueType( $dataTypeId ) ) {
			case 'wikibase-entityid':
				$this->assertFieldIsString( $valueSerialization, 'content', $basePath );
				$dataValue = $this->deserializeEntityIdValue( $dataTypeId, $valueSerialization['content'], $basePath );
				break;
			case 'time':
				$this->assertFieldIsArray( $valueSerialization, 'content', $basePath );
				$dataValue = $this->deserializeTimeValue( $valueSerialization['content'], $basePath );
				break;
			default:
				try {
					$dataValue = $this->snakValueDeserializer->deserialize( $dataTypeId, [
						'type' => $dataValueType,
						'value' => $valueSerialization['content'],
					] );
				} catch ( DeserializationException $e ) {
					throw new InvalidFieldException( 'content', $valueSerialization['content'], "$basePath/content" );
				}
				break;
		}

		foreach ( $this->validatorFactory->getValidators( $dataTypeId ) as $validator ) {
			$result = $validator->validate( $dataValue );
			if ( !$result->isValid() ) {
				throw new InvalidFieldException( 'content', $valueSerialization['content'], "$basePath/content" );
			}
		}

		return $dataValue;
	}

	private function deserializeEntityIdValue( string $dataTypeId, string $content, string $basePath ): DataValue {
		try {
			return $this->snakValueDeserializer->deserialize( $dataTypeId, [
				'value' => [ 'id' => $content ],
				'type' => 'wikibase-entityid',
			] );
		} catch ( DeserializationException $e ) {
			throw new InvalidFieldException( 'content', $content, "$basePath/content" );
		}
	}

	private function deserializeTimeValue( array $content, string $basePath ): TimeValue {
		$this->assertFieldExists( $content, 'time', $basePath );
		$this->assertFieldExists( $content, 'precision', $basePath );
		$this->assertFieldExists( $content, 'calendarmodel', $basePath );

		$this->assertFieldIsString( $content, 'time', $basePath );
		$this->assertFieldIsInt( $content, 'precision', $basePath );
		$this->assertFieldIsString( $content, 'calendarmodel', $basePath );

		$timestamp = $content['time'];
		$precision = $content['precision'];
		$calendarModel = $content['calendarmodel'];

		try {
			$timeValue = new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel );
		} catch ( IllegalValueException $e ) {
			throw new InvalidFieldException( 'content', $content, "$basePath/content" );
		}

		return $timeValue;
	}

	private function assertFieldExists( array $serialization, string $field, string $basePath ): void {
		if ( !array_key_exists( $field, $serialization ) ) {
			throw new MissingFieldException( $field, $basePath );
		}
	}

	private function assertFieldIsArray( array $serialization, string $field, string $basePath ): void {
		if ( !is_array( $serialization[$field] ) ) {
			throw new InvalidFieldException( $field, $serialization[$field], "$basePath/$field" );
		}
	}

	private function assertFieldIsString( array $serialization, string $field, string $basePath ): void {
		if ( !is_string( $serialization[$field] ) ) {
			throw new InvalidFieldException( $field, $serialization[$field], "$basePath/$field" );
		}
	}

	private function assertFieldIsInt( array $serialization, string $field, string $basePath ): void {
		if ( !is_int( $serialization[$field] ) ) {
			throw new InvalidFieldException( $field, $serialization[$field], "$basePath/$field" );
		}
	}

}
