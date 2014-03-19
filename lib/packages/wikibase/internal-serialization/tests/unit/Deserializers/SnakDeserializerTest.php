<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use DataValues\StringValue;
use Deserializers\Deserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SnakDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$dataValueDeserializer = $this->getMock( 'Deserializers\Deserializer' );

		$dataValueDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array( 'type' => 'string', 'value' => 'foo' ) ) )
			->will( $this->returnValue( new StringValue( 'foo' ) ) );

		$this->deserializer = new LegacySnakDeserializer( $dataValueDeserializer );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( array() ),
			array( array( 'novalue' ) ),
			array( array( 1337, 'novalue' ) ),
			array( array( 'spam', 1337 ) ),
			array( array( 'novalue', 'daah' ) ),
			array( array( 'novalue', 0 ) ),
			array( array( 'novalue', -1337 ) ),
			array( array( 'novalue', 1337, 'spam' ) ),
			array( array( 'value', 1337 ) ),
			array( array( 'value', 1337, 'data-value-type' ) ),
			array( array( 'value', 1337, 'data-value-type', 'data-value-value', 'spam' ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function testNoValueSnakDeserialization() {
		$this->assertEquals(
			new PropertyNoValueSnak( 42 ),
			$this->deserializer->deserialize( array(
				'novalue',
				42,
			) )
		);
	}

	public function testSomeValueSnakDeserialization() {
		$this->assertEquals(
			new PropertySomeValueSnak( 42 ),
			$this->deserializer->deserialize( array(
				'somevalue',
				42,
			) )
		);
	}

	public function testValueSnakDeserialization() {
		$this->assertEquals(
			new PropertyValueSnak( 42, new StringValue( 'foo' ) ),
			$this->deserializer->deserialize( array(
				'value',
				42,
				'string',
				'foo'
			) )
		);
	}

}