<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceDeserializerTest extends TestCase {

	private const EXISTING_STRING_PROPERTY_ID = 'P1968';

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDeserialize( Reference $expectedReference, array $serialization ): void {
		$this->assertEquals(
			$expectedReference,
			$this->newDeserializer()->deserialize( $serialization )
		);
	}

	public static function serializationProvider(): Generator {
		yield 'empty reference' => [
			new Reference(),
			[ 'parts' => [] ],
		];

		yield 'reference with two parts' => [
			new Reference( [
				new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_ID ) ),
				new PropertyNoValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_ID ) ),
			] ),
			[
				'parts' => [
					[
						'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
						'value' => [ 'type' => 'somevalue' ],
					],
					[
						'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_ID ],
						'value' => [ 'type' => 'novalue' ],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testDeserializationErrors( Exception $expectedException, array $serialization, string $basePath = '' ): void {
		try {
			$this->newDeserializer()->deserialize( $serialization, $basePath );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidSerializationProvider(): Generator {
		yield 'missing parts' => [
			new MissingFieldException( 'parts', '' ),
			[],
		];

		yield 'missing parts with path' => [
			new MissingFieldException( 'parts', 'references/0' ),
			[],
			'references/0',
		];

		yield 'invalid serialization' => [
			new InvalidFieldException( '', [ 'not', 'an', 'associative', 'array' ], '/some/path' ),
			[ 'not', 'an', 'associative', 'array' ],
			'/some/path',
		];

		yield 'null parts' => [
			new InvalidFieldException( 'parts', null, '/parts' ),
			[ 'parts' => null ],
		];

		yield "invalid 'parts' type - string" => [
			new InvalidFieldException( 'parts', 'not an array', '/parts' ),
			[ 'parts' => 'not an array' ],
		];

		yield "invalid 'parts' type - associative array" => [
			new InvalidFieldException( 'parts', [ 'invalid' => 'parts' ], '/parts' ),
			[ 'parts' => [ 'invalid' => 'parts' ] ],
		];

		yield "invalid 'parts/0' type - string" => [
			new InvalidFieldException( '0', 'potato', '/parts/0' ),
			[ 'parts' => [ 'potato' ] ],
		];

		yield "invalid 'parts/0' type - sequential array" => [
			new InvalidFieldException( '', [ 'not', 'an', 'associative', 'array' ], '/parts/0' ),
			[ 'parts' => [ [ 'not', 'an', 'associative', 'array' ] ] ],
		];

		yield "invalid 'parts/0' type with path" => [
			new InvalidFieldException( '0', 'potato', '/statement/references/1/parts/0' ),
			[ 'parts' => [ 'potato' ] ],
			'/statement/references/1',
		];
	}

	private function newDeserializer(): ReferenceDeserializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_ID ), 'string' );

		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			new BasicEntityIdParser(),
			$dataTypeLookup,
			new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
				WikibaseRepo::getSnakValueDeserializer(),
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);

		return new ReferenceDeserializer( $propertyValuePairDeserializer );
	}

}
