<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\Deserializers\EntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\PropertyDeserializer;
use Wikibase\InternalSerialization\Deserializers\TermsDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\PropertyDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new PropertyDeserializer(
			new EntityIdDeserializer( new BasicEntityIdParser() ),
			new TermsDeserializer()
		);
	}

	public function testGivenNonArraySerialization_deserializeThrowsException() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( null );
	}

	private function expectDeserializationException() {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
	}

	public function testGivenNoDataType_deserializeThrowsException() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array() );
	}

	public function testGivenNonStringDataType_deserializeThrowsException() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'datatype' => null ) );
	}

	public function testGivenValidDataType_dataTypeIsSet() {
		$property = $this->deserializer->deserialize( array( 'datatype' => 'foo' ) );
		$this->assertEquals( 'foo', $property->getDataTypeId() );
	}

}