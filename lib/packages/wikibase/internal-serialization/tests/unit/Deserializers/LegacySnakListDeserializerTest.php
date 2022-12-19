<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySnakListDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$snakDeserializer = new LegacySnakDeserializer( $this->createMock( Deserializer::class ) );

		$this->deserializer = new LegacySnakListDeserializer( $snakDeserializer );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ [ null ] ],
			[ [ 1337 ] ],
			[ [ [] ] ],
			[ [ [ 'novalue', 42 ], [ 'hax' ] ] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function testGivenEmptyArray_deserializeReturnsEmptySnakList() {
		$this->assertEquals(
			new SnakList( [] ),
			$this->deserializer->deserialize( [] )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsCorrectSnakList() {
		$expected = new SnakList( [
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 1337 ),
		] );

		$serialization = [
			[
				'novalue',
				42,
			],
			[
				'somevalue',
				1337,
			],
		];

		$this->assertEquals(
			$expected,
			$this->deserializer->deserialize( $serialization )
		);
	}

}
