<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use DataValues\StringValue;
use Deserializers\Deserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;

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

	public function setUp() {
		$dataValueDeserializer = $this->getMock( 'Deserializers\Deserializer' );

		$dataValueDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array( 'type' => 'string', 'value' => 'foo' ) ) )
			->will( $this->returnValue( new StringValue( 'foo' ) ) );

		$this->deserializer = new SnakDeserializer( $dataValueDeserializer );
	}

	/**
	 * @dataProvider snakProvider
	 */
	public function testSerializationRoundtripping( Snak $snak ) {
		$newSnak = $this->deserializer->deserialize( $snak->toArray() );

		$this->assertEquals( $snak, $newSnak );
	}

	public function snakProvider() {
		return array(
			array( new PropertyValueSnak( 42, new StringValue( 'foo' ) ) ),
			array( new PropertyNoValueSnak( 42 ) ),
			array( new PropertySomeValueSnak( 42 ) ),
		);
	}

}