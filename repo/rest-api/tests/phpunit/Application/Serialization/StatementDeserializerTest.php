<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Tests\RestApi\Helpers\TestPropertyValuePairDeserializerFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementDeserializerTest extends TestCase {

	private const EXISTING_STRING_PROPERTY_IDS = [ 'P7517', 'P5414', 'P8832', 'P2659' ];
	private const STATEMENT_ID = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

	/**
	 * @dataProvider validSerializationProvider
	 */
	public function testDeserialize( Statement $expectedStatement, array $serialization ): void {
		$this->assertTrue(
			$this->newDeserializer()->deserialize( $serialization )->equals( $expectedStatement )
		);
	}

	public static function validSerializationProvider(): Generator {
		yield 'without id' => [
			NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[0] )->build(),
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'with id' => [
			NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[1] )->withGuid( self::STATEMENT_ID )->build(),
			[
				'id' => self::STATEMENT_ID,
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'with preferred rank' => [
			NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[2] )->withPreferredRank()->build(),
			[
				'rank' => 'preferred',
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		$statementWithQualifiers = NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[1] )->build();
		$statementWithQualifiers->setQualifiers( new SnakList( [
			new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_IDS[2] ) ),
			new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_IDS[3] ) ),
		] ) );
		yield 'with qualifiers' => [
			$statementWithQualifiers,
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
				'value' => [ 'type' => 'somevalue' ],
				'qualifiers' => [
					[
						'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
						'value' => [ 'type' => 'somevalue' ],
					],
					[
						'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ],
						'value' => [ 'type' => 'somevalue' ],
					],
				],
			],
		];

		yield 'with references' => [
			NewStatement::someValueFor( self::EXISTING_STRING_PROPERTY_IDS[0] )
				->withReference( new Reference( [
					new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_IDS[1] ) ),
				] ) )
				->withReference( new Reference( [
					new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_IDS[2] ) ),
					new PropertySomeValueSnak( new NumericPropertyId( self::EXISTING_STRING_PROPERTY_IDS[3] ) ),
				] ) )
				->build(),
			[
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
				'value' => [ 'type' => 'somevalue' ],
				'references' => [
					[
						'parts' => [
							[
								'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
								'value' => [ 'type' => 'somevalue' ],
							],
						],
					],
					[
						'parts' => [
							[
								'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
								'value' => [ 'type' => 'somevalue' ],
							],
							[
								'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ],
								'value' => [ 'type' => 'somevalue' ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testDeserializationErrors( SerializationException $expectedException, array $serialization, string $basePath ): void {
		try {
			$this->newDeserializer()->deserialize( $serialization, $basePath );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function invalidSerializationProvider(): Generator {
		$serialization = [
			[ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
			[ 'type' => 'somevalue' ],
		];
		yield 'statement is not associative array' => [
			new InvalidFieldTypeException( '/statements/P789', '/statements/P789', $serialization ),
			$serialization,
			'/statements/P789',
		];

		yield "invalid 'id' field - array" => [
			new InvalidFieldException( 'id', [ self::STATEMENT_ID ], '/statement/id' ),
			[
				'id' => [ self::STATEMENT_ID ],
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statement',
		];

		yield "invalid 'rank' field" => [
			new InvalidFieldException( 'rank', 'bad', '/statements/P789/rank' ),
			[
				'rank' => 'bad',
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statements/P789',
		];

		yield "invalid 'qualifiers' field - string" => [
			new InvalidFieldException( 'qualifiers', 'invalid', '/statement/qualifiers' ),
			[
				'qualifiers' => 'invalid',
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statement',
		];

		yield "invalid 'qualifiers' field - associative array" => [
			new InvalidFieldException( 'qualifiers', [ 'invalid' => 'qualifiers' ], '/statement/qualifiers' ),
			[
				'qualifiers' => [ 'invalid' => 'qualifiers' ],
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statement',
		];

		yield "invalid 'qualifiers/0' field - string" => [
			new InvalidFieldException( '0', 'invalid', '/some/path/qualifiers/0' ),
			[
				'qualifiers' => [ 'invalid' ],
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/some/path',
		];

		yield "invalid 'references' field - string" => [
			new InvalidFieldException( 'references', 'invalid', '/statements/P789/references' ),
			[
				'references' => 'invalid',
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statements/P789',
		];

		yield "invalid 'references' field - associative array" => [
			new InvalidFieldException( 'references', [ 'invalid' => 'references' ], '/statements/P789/references' ),
			[
				'references' => [ 'invalid' => 'references' ],
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[1] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statements/P789',
		];

		yield "invalid 'references/0' field - string" => [
			new InvalidFieldException( '0', 'invalid', '/statement/references/0' ),
			[
				'references' => [ 'invalid' ],
				'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[2] ],
				'value' => [ 'type' => 'somevalue' ],
			],
			'/statement',
		];

		yield "missing 'property' field" => [
			new MissingFieldException( 'property', '/statement' ),
			[ 'value' => [ 'type' => 'somevalue' ] ],
			'/statement',
		];

		yield "missing 'value' field" => [
			new MissingFieldException( 'value', '/statement' ),
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[3] ] ],
			'/statement',
		];

		yield "missing 'value/content' field" => [
			new MissingFieldException( 'content', '/statement/value' ),
			[ 'property' => [ 'id' => self::EXISTING_STRING_PROPERTY_IDS[0] ], 'value' => [ 'type' => 'value' ] ],
			'/statement',
		];
	}

	private function newDeserializer(): StatementDeserializer {
		$deserializerFactory = new TestPropertyValuePairDeserializerFactory();
		$deserializerFactory->setDataTypeForProperties( array_fill_keys( self::EXISTING_STRING_PROPERTY_IDS, 'string' ) );
		$propertyValuePairDeserializer = $deserializerFactory->createPropertyValuePairDeserializer();
		$referenceDeserializer = new ReferenceDeserializer( $propertyValuePairDeserializer );

		return new StatementDeserializer( $propertyValuePairDeserializer, $referenceDeserializer );
	}

}
