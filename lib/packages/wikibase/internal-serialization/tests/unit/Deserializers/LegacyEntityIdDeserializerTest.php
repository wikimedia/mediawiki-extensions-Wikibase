<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityIdDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = new LegacyEntityIdDeserializer( new BasicEntityIdParser() );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ 42 ],
			[ [] ],
			[ [ 'Q42' ] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( $serialization );
	}

	private function expectDeserializationException() {
		$this->expectException( DeserializationException::class );
	}

	/**
	 * @dataProvider legacyIdProvider
	 */
	public function testGivenLegacyIdFormat_deserializationIsCorrect( EntityId $expectedId, array $legacyFormat ) {
		$actualId = $this->deserializer->deserialize( $legacyFormat );
		$this->assertEquals( $expectedId, $actualId );
	}

	public function legacyIdProvider() {
		return [
			[
				new ItemId( 'Q42' ),
				[ 'item', 42 ],
			],

			[
				new NumericPropertyId( 'P1337' ),
				[ 'property', 1337 ],
			],
		];
	}

	/**
	 * @dataProvider newIdProvider
	 */
	public function testGivenNewIdFormat_deserializationIsCorrect( EntityId $expectedId, $idSerialization ) {
		$actualId = $this->deserializer->deserialize( $idSerialization );
		$this->assertEquals( $expectedId, $actualId );
	}

	public function newIdProvider() {
		return [
			[ new ItemId( 'Q1' ), 'Q1' ],
			[ new ItemId( 'Q42' ), 'q42' ],
			[ new NumericPropertyId( 'P1337' ), 'P1337' ],
			[ new NumericPropertyId( 'P23' ), 'p23' ],
		];
	}

	public function testGivenInvalidNewIdFormat_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( 'Q42spam' );
	}

	public function testGivenInvalidLegacyIdFormat_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'item', 'foobar' ] );
	}

	public function testGivenArrayWithTwoStringKeys_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [
			'foo' => 'item',
			'baz' => 42,
		] );
	}

	public function testGivenArrayWithWrongNumericKeys_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [
			42 => 'item',
			1337 => 42,
		] );
	}

}
