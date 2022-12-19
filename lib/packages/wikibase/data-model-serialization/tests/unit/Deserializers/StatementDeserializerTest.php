<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Deserializers\StatementDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class StatementDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$snakDeserializer = $this->createMock( Deserializer::class );
		$snakDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
					'snaktype' => 'novalue',
					'property' => 'P42',
			] ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		$snakListDeserializer = $this->createMock( Deserializer::class );
		$snakListDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'P42' => [
					[
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
				],
			] ) )
			->will( $this->returnValue( new SnakList( [
				new PropertyNoValueSnak( 42 ),
			] ) ) );

		$referenceListDeserializer = $this->createMock( Deserializer::class );
		$referenceListDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [] ) )
			->will( $this->returnValue( new ReferenceList() ) );

		return new StatementDeserializer(
			$snakDeserializer,
			$snakListDeserializer,
			$referenceListDeserializer
		);
	}

	public function deserializableProvider() {
		return [
			[
				[
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
					'type' => 'claim',
				],
			],
			[
				[
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
					'type' => 'statement',
					'rank' => 'normal',
				],
			],
		];
	}

	public function nonDeserializableProvider() {
		return [
			[
				42,
			],
			[
				[
					'id' => 'P10',
				],
			],
			[
				[
					'type' => '42',
				],
			],
		];
	}

	public function deserializationProvider() {
		$serializations = [];

		$serializations[] = [
			new Statement( new PropertyNoValueSnak( 42 ) ),
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'claim',
			],
		];

		$serializations[] = [
			new Statement( new PropertyNoValueSnak( 42 ) ),
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'q42' );
		$serializations[] = [
			$statement,
			[
				'id' => 'q42',
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'claim',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'preferred',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_NORMAL );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'deprecated',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [] ) );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [
			new PropertyNoValueSnak( 42 ),
		] ) );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'qualifiers' => [
					'P42' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				],
				'type' => 'statement',
				'rank' => 'normal',
			],
		];

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setReferences( new ReferenceList() );
		$serializations[] = [
			$statement,
			[
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => "P42",
				],
				'references' => [],
				'type' => 'statement',
				'rank' => 'normal',
			],
		];

		return $serializations;
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return [
			[
				[
					'type' => 'claim',
				],
			],
			[
				[
					'id' => 42,
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
					'type' => 'claim',
				],
			],
			[
				[
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
					'type' => 'statement',
					'rank' => 'nyan-cat',
				],
			],
		];
	}

	public function testQualifiersOrderDeserialization() {
		$snakDeserializerMock = $this->createMock( Deserializer::class );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'snaktype' => 'novalue',
				'property' => 'P42',
			] ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		$snaksDeserializerMock = $this->createMock( Deserializer::class );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
					'P24' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P24',
						],
					],
					'P42' => [
						[
							'snaktype' => 'somevalue',
							'property' => 'P42',
						],
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				]
			) )
			->will( $this->returnValue( new SnakList( [
				new PropertyNoValueSnak( 24 ),
				new PropertySomeValueSnak( 42 ),
				new PropertyNoValueSnak( 42 ),
			] ) ) );

		$referencesDeserializerMock = $this->createMock( Deserializer::class );
		$statementDeserializer = new StatementDeserializer(
			$snakDeserializerMock,
			$snaksDeserializerMock,
			$referencesDeserializerMock
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( [
			new PropertySomeValueSnak( 42 ),
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 24 ),
		] ) );

		$serialization = [
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => 'P42',
			],
			'qualifiers' => [
				'P24' => [
					[
						'snaktype' => 'novalue',
						'property' => 'P24',
					],
				],
				'P42' => [
					[
						'snaktype' => 'somevalue',
						'property' => 'P42',
					],
					[
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
				],
			],
			'qualifiers-order' => [
				'P42',
				'P24',
			],
			'type' => 'claim',
		];

		$this->assertSame(
			$statement->getHash(),
			$statementDeserializer->deserialize( $serialization )->getHash()
		);
	}

	public function testQualifiersOrderDeserializationWithTypeError() {
		$serialization = [
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => 'P42',
			],
			'qualifiers' => [
				'P42' => [
					[
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
				],
			],
			'qualifiers-order' => 'stringInsteadOfArray',
			'type' => 'claim',
		];

		$deserializer = $this->buildDeserializer();

		$this->expectException( InvalidAttributeException::class );
		$deserializer->deserialize( $serialization );
	}

}
