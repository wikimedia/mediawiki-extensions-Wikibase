<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakListDeserializerTest extends TestCase {

	private function buildDeserializer() {
		$snakDeserializerMock = $this->createMock( Deserializer::class );

		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
					'snaktype' => 'novalue',
					'property' => 'P42',
			] ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		return new SnakListDeserializer( $snakDeserializerMock );
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
					'snaktype' => '42value',
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
		return [
			[
				new SnakList(),
				[],
			],
			[
				new SnakList( [
					new PropertyNoValueSnak( 42 ),
				] ),
				[
					'P42' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				],
			],
		];
	}

}
