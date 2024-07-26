<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValuePairDeserializerTest extends TestCase {

	private const STRING_PROPERTY_ID = 'P123';
	private const URL_PROPERTY_ID = 'P789';
	private const ITEM_ID_PROPERTY_ID = 'P321';
	private const TIME_PROPERTY_ID = 'P456';
	private const GLOBECOORDINATE_PROPERTY_ID = 'P678';
	private const STRING_URI_PROPERTY_ID = 'https://example.com/P1';

	/**
	 * @dataProvider validSerializationProvider
	 */
	public function testDeserialize( Snak $expectedSnak, array $serialization ): void {
		$this->assertEquals( $expectedSnak, $this->newDeserializer()->deserialize( $serialization ) );
	}

	public function validSerializationProvider(): Generator {
		yield 'no value for string property' => [
			new PropertyNoValueSnak( new NumericPropertyId( self::STRING_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'novalue' ],
				'property' => [
					'id' => self::STRING_PROPERTY_ID,
				],
			],
		];

		yield 'some value for item id property' => [
			new PropertySomeValueSnak( new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::ITEM_ID_PROPERTY_ID,
				],
			],
		];

		yield 'non-numeric property id (e.g. federated property)' => [
			new PropertySomeValueSnak( $this->newUriPropertyId( self::STRING_URI_PROPERTY_ID ) ),
			[
				'value' => [ 'type' => 'somevalue' ],
				'property' => [
					'id' => self::STRING_URI_PROPERTY_ID,
				],
			],
		];

		yield 'value for string property' => [
			new PropertyValueSnak(
				new NumericPropertyId( self::STRING_PROPERTY_ID ),
				new StringValue( 'potato' )
			),
			[
				'value' => [ 'type' => 'value', 'content' => 'potato' ],
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_throwsSerializationException(
		SerializationException $expectedException,
		array $serialization,
		string $basePath
	): void {
		try {
			$this->newDeserializer()->deserialize( $serialization, $basePath );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidSerializationProvider(): Generator {
		yield 'invalid serialization' => [
			new InvalidFieldException( '', [ 'not', 'an', 'associative', 'array' ], '/some/path' ),
			[ 'not', 'an', 'associative', 'array' ],
			'/some/path',
		];

		yield "invalid 'property' field - int" => [
			new InvalidFieldException( 'property', 42, '/statement/references/3/parts/1/property' ),
			[
				'property' => 42,
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];

		yield "invalid 'property' field - sequential array" => [
			new InvalidFieldException( 'property', [ 'not an associative array' ], '/some/path/property' ),
			[
				'property' => [ 'not an associative array' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/some/path',
		];

		yield "invalid 'property/id' field" => [
			new InvalidFieldException( 'id', 'not-a-property-id', '/statements/P789/0/property/id' ),
			[
				'property' => [ 'id' => 'not-a-property-id' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statements/P789/0',
		];

		yield "invalid 'property/id' field - item id" => [
			new InvalidFieldException( 'id', 'Q123', '/statements/P789/2/qualifiers/1/property/id' ),
			[
				'property' => [ 'id' => 'Q123' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statements/P789/2/qualifiers/1',
		];

		yield "invalid 'property/id' field - property does not exist" => [
			new InvalidFieldException( 'id', 'P666', '/statement/references/3/parts/1/property/id' ),
			[
				'property' => [ 'id' => 'P666' ],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];

		yield "invalid 'value' field - int" => [
			new InvalidFieldException( 'value', 42, '/statements/P789/0/value' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => 42,
			],
			'/statements/P789/0',
		];

		yield "invalid 'value' field - sequential array" => [
			new InvalidFieldException( 'value', [ 'not an associative array' ], '/statement/value' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => [ 'not an associative array' ],
			],
			'/statement',
		];

		yield "invalid 'value/type' field - not one of the allowed value" => [
			new InvalidFieldException( 'type', 'not-a-value-type', '/statements/P789/2/qualifiers/1/value/type' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'not-a-value-type', 'content' => 'I am goat' ],
			],
			'/statements/P789/2/qualifiers/1',
		];

		yield "invalid 'value/content' field" => [
			new InvalidFieldException( 'content', 42, '/statements/P789/3/references/2/parts/1/value/content' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => [ 'type' => 'value', 'content' => 42 ],
			],
			'/statements/P789/3/references/2/parts/1',
		];

		yield "missing 'property' field" => [
			new MissingFieldException( 'property', '/statements/P789/2/qualifiers/1' ),
			[ 'value' => [ 'type' => 'novalue' ] ],
			'/statements/P789/2/qualifiers/1',
		];

		yield "missing 'property/id' field" => [
			new MissingFieldException( 'id', '/statement/references/3/parts/1/property' ),
			[
				'property' => [],
				'value' => [ 'type' => 'novalue' ],
			],
			'/statement/references/3/parts/1',
		];

		yield "missing 'value' field" => [
			new MissingFieldException( 'value', '/some/path' ),
			[ 'property' => [ 'id' => self::STRING_PROPERTY_ID ] ],
			'/some/path',
		];

		yield "missing 'value/type' field" => [
			new MissingFieldException( 'type', '/statements/P789/0/value' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => [ 'content' => 'I am goat' ],
			],
			'/statements/P789/0',
		];

		yield "missing 'value/type' field - empty array" => [
			new MissingFieldException( 'type', '/statement/value' ),
			[
				'property' => [ 'id' => self::STRING_PROPERTY_ID ],
				'value' => [],
			],
			'/statement',
		];
	}

	private function newDeserializer(): PropertyValuePairDeserializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::STRING_PROPERTY_ID ),
			'string'
		);
		$dataTypeLookup->setDataTypeForProperty(
			$this->newUriPropertyId( self::URL_PROPERTY_ID ),
			'url'
		);
		$dataTypeLookup->setDataTypeForProperty(
			$this->newUriPropertyId( self::TIME_PROPERTY_ID ),
			'time'
		);
		$dataTypeLookup->setDataTypeForProperty(
			$this->newUriPropertyId( self::GLOBECOORDINATE_PROPERTY_ID ),
			'globe-coordinate'
		);
		$dataTypeLookup->setDataTypeForProperty(
			new NumericPropertyId( self::ITEM_ID_PROPERTY_ID ),
			'wikibase-item'
		);
		$dataTypeLookup->setDataTypeForProperty(
			$this->newUriPropertyId( self::STRING_URI_PROPERTY_ID ),
			'string'
		);

		$entityIdParser = $this->createStub( EntityIdParser::class );
		$entityIdParser->method( 'parse' )->willReturnCallback( function( $id ) {
			if ( substr( $id, 0, 4 ) === 'http' ) {
				return $this->newUriPropertyId( $id );
			}

			return WikibaseRepo::getEntityIdParser()->parse( $id );
		} );

		$valueDeserializer = new DataValuesValueDeserializer(
			new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
			WikibaseRepo::getSnakValueDeserializer(),
			WikibaseRepo::getDataTypeValidatorFactory()
		);

		return new PropertyValuePairDeserializer( $entityIdParser, $dataTypeLookup, $valueDeserializer );
	}

	private function newUriPropertyId( string $uri ): PropertyId {
		$id = $this->createStub( PropertyId::class );
		$id->method( 'getEntityType' )->willReturn( Property::ENTITY_TYPE );
		$id->method( 'getSerialization' )->willReturn( $uri );

		return $id;
	}

}
