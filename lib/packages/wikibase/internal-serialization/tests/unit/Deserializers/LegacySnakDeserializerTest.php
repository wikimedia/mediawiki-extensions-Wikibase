<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$dataValueDeserializer = $this->getMock( Deserializer::class );

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
		$this->setExpectedException( DeserializationException::class );
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

	public function testGivenInvalidDataValue_unDerializableValueIsConstructed() {
		$dataValueDeserializer = new DataValueDeserializer( array(
			'string' => StringValue::class
		) );

		$deserializer = new LegacySnakDeserializer( $dataValueDeserializer );

		$snak = $deserializer->deserialize( array(
			'value',
			42,
			'string',
			1337
		) );

		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
		$this->assertSnakHasUnDeseriableValue( $snak );
	}

	private function assertSnakHasUnDeseriableValue( PropertyValueSnak $snak ) {
		$this->assertEquals( new PropertyId( 'P42' ), $snak->getPropertyId() );

		$dataValue = $snak->getDataValue();

		/**
		 * @var UnDeserializableValue $dataValue
		 */
		$this->assertInstanceOf( UnDeserializableValue::class, $dataValue );

		$this->assertEquals( $dataValue->getTargetType(), 'string' );
		$this->assertEquals( $dataValue->getValue(), 1337 );
	}

}
