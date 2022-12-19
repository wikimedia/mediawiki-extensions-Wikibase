<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Deserializers\StatementListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementListDeserializerTest extends TestCase {

	private function buildDeserializer() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementDeserializerMock = $this->createMock( Deserializer::class );
		$statementDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				'type' => 'statement',
				'rank' => 'normal',
			] ) )
			->will( $this->returnValue( $statement ) );

		return new StatementListDeserializer( $statementDeserializerMock );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
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

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals( $object, $this->buildDeserializer()->deserialize( $serialization ) );
	}

	public function deserializationProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return [
			[
				new StatementList(),
				[],
			],
			[
				new StatementList( $statement ),
				[
					'P42' => [
						[
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => 'P42',
							],
							'type' => 'statement',
							'rank' => 'normal',
						],
					],
				],
			],
		];
	}

}
