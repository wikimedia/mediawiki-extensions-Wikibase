<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Generator;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Serialization\SerializationException;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataValuesValueDeserializerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$successValidator = $this->createStub( ValueValidator::class );
		$successValidator->method( 'validate' )->willReturn( Result::newSuccess() );

		$this->dataTypeValidatorFactory = $this->createStub( DataTypeValidatorFactory::class );
		$this->dataTypeValidatorFactory->method( 'getValidators' )->willReturn( [ $successValidator ] );
	}

	/**
	 * @dataProvider provideValidInput
	 */
	public function testGivenValidInput_deserializeReturnsDataValue(
		string $dataTypeId,
		array $valueSerialization,
		DataValue $expectedDataValue
	): void {
		$this->assertEquals(
			$expectedDataValue,
			$this->newDeserializer()->deserialize( $dataTypeId, $valueSerialization )
		);
	}

	public function provideValidInput(): Generator {
		yield 'value for string property' => [
			'string',
			[ 'type' => 'value', 'content' => 'I am goat' ],
			new StringValue( 'I am goat' ),
		];

		yield 'value for item id prop' => [
			'wikibase-item',
			[ 'type' => 'value', 'content' => 'Q123' ],
			new EntityIdValue( new ItemId( 'Q123' ) ),
		];

		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		yield 'value for time property' => [
			'time',
			[
				'type' => 'value',
				'content' => [
					'time' => '+1-00-00T00:00:00Z',
					'precision' => TimeValue::PRECISION_YEAR,
					'calendarmodel' => $gregorian,
				],
			],
			new TimeValue( '+1-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $gregorian ),
		];

		yield 'value for globecoordinate property' => [
			'globe-coordinate',
			[
				'type' => 'value',
				'content' => [
					'latitude' => 100,
					'longitude' => 100,
				],
			],
			new GlobeCoordinateValue( new LatLongValue( 100, 100 ) ),
		];
	}

	/**
	 * @dataProvider provideInvalidInput
	 */
	public function testGivenInvalidInput_deserializeThrowsException(
		string $dataTypeId,
		array $valueSerialization,
		SerializationException $expectedException
	): void {
		$this->dataTypeValidatorFactory = WikibaseRepo::getDataTypeValidatorFactory();

		try {
			$this->newDeserializer()->deserialize( $dataTypeId, $valueSerialization );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public function provideInvalidInput(): Generator {
		yield 'invalid content field for string data-type' => [
			'string',
			[ 'type' => 'value', 'content' => 42 ],
			new InvalidFieldException( 'content', 42 ),
		];

		yield 'invalid content field for url data-type' => [
			'url',
			[ 'type' => 'value', 'content' => 'not-a-url' ],
			new InvalidFieldException( 'content', 'not-a-url' ),
		];

		yield 'invalid content field for wikibase-item data-type' => [
			'wikibase-item',
			[ 'type' => 'value', 'content' => 'X123' ],
			new InvalidFieldException( 'content', 'X123' ),
		];

		yield 'invalid content field for time data-type (string)' => [
			'time',
			[ 'type' => 'value', 'content' => '+1-00-00T00:00:00Z' ],
			new InvalidFieldException( 'content', '+1-00-00T00:00:00Z' ),
		];

		yield 'invalid content field for time data-type (int)' => [
			'time',
			[ 'type' => 'value', 'content' => 1672628645 ],
			new InvalidFieldException( 'content', 1672628645 ),
		];

		yield 'missing content field' => [
			'string',
			'value' => [ 'type' => 'value' ],
			new MissingFieldException( 'content' ),
		];
	}

	private function newDeserializer(): DataValuesValueDeserializer {
		return new DataValuesValueDeserializer(
			new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getDataValueDeserializer(),
			$this->dataTypeValidatorFactory
		);
	}

}
