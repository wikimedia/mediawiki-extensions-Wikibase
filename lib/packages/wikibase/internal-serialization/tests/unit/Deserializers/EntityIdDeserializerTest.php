<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\EntityIdDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new LegacyEntityIdDeserializer( new BasicEntityIdParser() );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 42 ),
			array( array() ),
			array( array( 'Q42' ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( $serialization );
	}

	private function expectDeserializationException() {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
	}

	/**
	 * @dataProvider legacyIdProvider
	 */
	public function testGivenLegacyIdFormat_deserializationIsCorrect( EntityId $expectedId, array $legacyFormat ) {
		$actualId = $this->deserializer->deserialize( $legacyFormat );
		$this->assertEquals( $expectedId, $actualId );
	}

	public function legacyIdProvider() {
		return array(
			array(
				new ItemId( 'Q42' ),
				array( 'item', 42 )
			),

			array(
				new PropertyId( 'P1337' ),
				array( 'property', 1337 )
			),
		);
	}

	/**
	 * @dataProvider newIdProvider
	 */
	public function testGivenNewIdFormat_deserializationIsCorrect( EntityId $expectedId, $idSerialization ) {
		$actualId = $this->deserializer->deserialize( $idSerialization );
		$this->assertEquals( $expectedId, $actualId );
	}

	public function newIdProvider() {
		return array(
			array( new ItemId( 'Q1' ), 'Q1' ),
			array( new ItemId( 'Q42' ), 'q42' ),
			array( new PropertyId( 'P1337' ), 'P1337' ),
			array( new PropertyId( 'P23' ), 'p23' ),
		);
	}

	public function testGivenInvalidNewIdFormat_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( 'Q42spam' );
	}

	public function testGivenInvalidLegacyIdFormat_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'item', 'foobar' ) );
	}

	public function testGivenArrayWithTwoStringKeys_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array(
			'foo' => 'item',
			'baz' => 42,
		) );
	}

	public function testGivenArrayWithWrongNumericKeys_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array(
			42 => 'item',
			1337 => 42,
		) );
	}

}