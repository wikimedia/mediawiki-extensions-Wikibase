<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyPropertyDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyPropertyDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = new LegacyPropertyDeserializer(
			new LegacyEntityIdDeserializer( new BasicEntityIdParser() ),
			new LegacyFingerprintDeserializer()
		);
	}

	public function testGivenNonArraySerialization_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( null );
	}

	private function expectDeserializationException() {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
	}

	public function testGivenNoDataType_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array() );
	}

	public function testGivenNonStringDataType_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'datatype' => null ) );
	}

	public function testGivenValidDataType_dataTypeIsSet() {
		$property = $this->deserializer->deserialize( array( 'datatype' => 'foo' ) );
		$this->assertEquals( 'foo', $property->getDataTypeId() );
	}

	public function testGivenInvalidEntityId_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'entity' => 'spam spam spam'
		) );
	}

	public function testGivenNonPropertyEntityId_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'entity' => 'q42'
		) );
	}

	public function testGivenNoPropertyIdId_noPropertyIdIsSet() {
		$property = $this->deserializer->deserialize( array( 'datatype' => 'foo' ) );
		$this->assertNull( $property->getId() );
	}

	public function testGivenValidPropertyIdId_propertyIdIsSet() {
		$property = $this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'entity' => 'p42'
		) );

		$this->assertEquals( new PropertyId( 'p42' ), $property->getId() );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labels ) {
		$property = $this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'label' => $labels
		) );

		$this->assertEquals( $labels, $property->getLabels() );
	}

	public function TermListProvider() {
		return array(
			array( array() ),

			array( array(
				'en' => 'foo',
				'de' => 'bar',
			) ),
		);
	}

	public function testGivenInvalidLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'label' => null ) );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptions ) {
		$property = $this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'description' => $descriptions
		) );

		$this->assertEquals( $descriptions, $property->getDescriptions() );
	}

	public function testGivenInvalidAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'aliases' => null ) );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliases ) {
		$property = $this->deserializer->deserialize( array(
			'datatype' => 'foo',
			'aliases' => $aliases
		) );

		$this->assertEquals( $aliases, $property->getAllAliases() );
	}

	public function aliasesListProvider() {
		return array(
			array( array() ),

			array( array(
				'en' => array( 'foo', 'bar' ),
				'de' => array( 'foo', 'bar', 'baz' ),
				'nl' => array( 'bah' ),
			) ),
		);
	}

}
