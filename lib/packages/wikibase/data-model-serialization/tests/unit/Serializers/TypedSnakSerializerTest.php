<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Snak\TypedSnak;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\TypedSnakSerializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnakSerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Serializer
	 */
	private $serializer;

	public function setUp() {
		$snakSerializer = $this->getMock( 'Serializers\Serializer' );

		$snakSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'foo' => 'bar',
				'baz' => 42
			) ) );

		$this->serializer = new TypedSnakSerializer( $snakSerializer );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testDataTypeIsAddedToSnakSerialization( TypedSnak $input, array $expected ) {
		$actualSerialization = $this->serializer->serialize( $input );

		$this->assertEquals( $expected, $actualSerialization );
	}

	public function serializationProvider() {
		$argLists = array();

		$mockSnak = $this->getMock( 'Wikibase\DataModel\Snak\Snak' );

		$argLists[] = array(
			new TypedSnak( $mockSnak, 'string' ),
			array(
				'foo' => 'bar',
				'baz' => 42,
				'datatype' => 'string',
			)
		);

		$argLists[] = array(
			new TypedSnak( $mockSnak, 'kittens' ),
			array(
				'foo' => 'bar',
				'baz' => 42,
				'datatype' => 'kittens',
			)
		);

		return $argLists;
	}

	public function testWithUnsupportedObject() {
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$this->serializer->serialize( new PropertyNoValueSnak( 42 ) );
	}

}
