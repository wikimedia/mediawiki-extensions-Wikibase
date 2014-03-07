<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SnakListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakListDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$snakDeserializer = new SnakDeserializer( $this->getMock( 'Deserializers\Deserializer' ) );

		$this->deserializer = new SnakListDeserializer( $snakDeserializer );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( array( null ) ),
			array( array( 1337 ) ),
			array( array( array() ) ),
			array( array( array( 'novalue', 42 ), array( 'hax' ) ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function testGivenEmptyArray_deserializeReturnsEmptySnakList() {
		$this->assertEquals(
			new SnakList( array() ),
			$this->deserializer->deserialize( array() )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsCorrectSnakList() {
		$expected = new SnakList( array(
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 1337 ),
		) );

		$serialization = array(
			array(
				'novalue',
				42,
			),
			array(
				'somevalue',
				1337,
			)
		);

		$this->assertEquals(
			$expected,
			$this->deserializer->deserialize( $serialization )
		);
	}

}