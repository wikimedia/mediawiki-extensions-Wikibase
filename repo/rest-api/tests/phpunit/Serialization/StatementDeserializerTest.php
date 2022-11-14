<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\StatementDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementDeserializerTest extends TestCase {

	private const STATEMENT_ID = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDeserialize( Statement $expectedStatement, array $serialization ): void {
		$this->assertTrue(
			$this->newDeserializer()->deserialize( $serialization )
				->equals( $expectedStatement )
		);
	}

	public function serializationProvider(): Generator {
		yield 'without id' => [
			NewStatement::someValueFor( 'P123' )->build(),
			[
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'with id' => [
			NewStatement::someValueFor( 'P234' )
				->withGuid( self::STATEMENT_ID )
				->build(),
			[
				'property' => [ 'id' => 'P234' ],
				'value' => [ 'type' => 'somevalue' ],
				'id' => self::STATEMENT_ID,
			],
		];

		$statementWithQualifiers = NewStatement::someValueFor( 'P666' )->build();
		$statementWithQualifiers->setQualifiers( new SnakList( [
			new PropertySomeValueSnak( new NumericPropertyId( 'P777' ) ),
			new PropertySomeValueSnak( new NumericPropertyId( 'P888' ) ),
		] ) );
		yield 'with qualifiers' => [
			$statementWithQualifiers,
			[
				'property' => [ 'id' => 'P666' ],
				'value' => [ 'type' => 'somevalue' ],
				'qualifiers' => [
					[
						'property' => [ 'id' => 'P777' ],
						'value' => [ 'type' => 'somevalue' ],
					],
					[
						'property' => [ 'id' => 'P888' ],
						'value' => [ 'type' => 'somevalue' ],
					],
				],
			],
		];

		yield 'with preferred rank' => [
			NewStatement::someValueFor( 'P23' )
				->withPreferredRank()
				->build(),
			[
				'property' => [ 'id' => 'P23' ],
				'value' => [ 'type' => 'somevalue' ],
				'rank' => 'preferred',
			],
		];

		yield 'with references' => [
			NewStatement::someValueFor( 'P23' )
				->withReference( new Reference( [
					new PropertySomeValueSnak( new NumericPropertyId( 'P234' ) ),
				] ) )
				->withReference( new Reference( [
					new PropertySomeValueSnak( new NumericPropertyId( 'P345' ) ),
					new PropertySomeValueSnak( new NumericPropertyId( 'P456' ) ),
				] ) )
				->build(),
			[
				'property' => [ 'id' => 'P23' ],
				'value' => [ 'type' => 'somevalue' ],
				'references' => [
					[
						'parts' => [
							[
								'property' => [ 'id' => 'P234' ],
								'value' => [ 'type' => 'somevalue' ],
							],
						]
					],
					[
						'parts' => [
							[
								'property' => [ 'id' => 'P345' ],
								'value' => [ 'type' => 'somevalue' ],
							],
							[
								'property' => [ 'id' => 'P456' ],
								'value' => [ 'type' => 'somevalue' ],
							],
						]
					],
				]
			],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testDeserializationErrors( string $expectedException, array $serialization ): void {
		$this->expectException( $expectedException );

		$this->newDeserializer()->deserialize( $serialization );
	}

	public function invalidSerializationProvider(): Generator {
		yield 'invalid id field type' => [
			InvalidFieldException::class,
			[
				'id' => [ 'invalid' ],
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			]
		];

		yield 'invalid rank' => [
			InvalidFieldException::class,
			[
				'rank' => 'bad',
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'invalid qualifiers field type' => [
			InvalidFieldException::class,
			[
				'qualifiers' => 'invalid',
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'invalid qualifier item type' => [
			InvalidFieldException::class,
			[
				'qualifiers' => [ 'invalid' ],
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'invalid references field type' => [
			InvalidFieldException::class,
			[
				'references' => 'invalid',
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];

		yield 'invalid reference item type' => [
			InvalidFieldException::class,
			[
				'references' => [ 'invalid' ],
				'property' => [ 'id' => 'P123' ],
				'value' => [ 'type' => 'somevalue' ],
			],
		];
	}

	private function newDeserializer(): StatementDeserializer {
		$newSomeValueSnakFromSerialization = fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p['property']['id'] ) );

		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )
			->willReturnCallback( $newSomeValueSnakFromSerialization );

		$referenceDeserializer = $this->createStub( ReferenceDeserializer::class );
		$referenceDeserializer->method( 'deserialize' )
			->willReturnCallback( fn( array $ref ) => new Reference(
				array_map( $newSomeValueSnakFromSerialization, $ref['parts'] )
			) );

		return new StatementDeserializer( $propValPairDeserializer, $referenceDeserializer );
	}

}
