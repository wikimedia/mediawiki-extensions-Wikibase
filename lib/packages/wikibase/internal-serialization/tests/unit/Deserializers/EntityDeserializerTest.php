<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\InternalSerialization\Deserializers\EntityDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\EntityDeserializer
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = new EntityDeserializer(
			$this->getStubLegacyDeserializer(),
			$this->getStubCurrentDeserializer()
		);
	}

	private function getStubLegacyDeserializer() {
		$legacyDeserializer = $this->getMock( 'Deserializers\DispatchableDeserializer' );

		$legacyDeserializer->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->will( $this->returnCallback( function( $serialization ) {
				return array_key_exists( 'entity', $serialization );
			} ) );

		$legacyDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( 'legacy' ) );

		return $legacyDeserializer;
	}

	private function getStubCurrentDeserializer() {
		$currentDeserializer = $this->getMock( 'Deserializers\DispatchableDeserializer' );

		$currentDeserializer->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->will( $this->returnCallback( function( $serialization ) {
				return array_key_exists( 'id', $serialization );
			} ) );

		$currentDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( 'current' ) );

		return $currentDeserializer;
	}

	public function testGivenLegacySerialization_legacyIsDetected() {
		$returnValue = $this->deserializer->deserialize( array( 'entity' => array( 'item', 1 ) ) );
		$this->assertEquals( 'legacy', $returnValue );
	}

	public function testGivenCurrentSerialization_currentIsDetected() {
		$returnValue = $this->deserializer->deserialize( array( 'id' => 'Q1' ) );
		$this->assertEquals( 'current', $returnValue );
	}

	private function getThrowingDeserializer() {
		$currentDeserializer = $this->getMock( 'Deserializers\DispatchableDeserializer' );

		$currentDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->throwException( new DeserializationException() ) );

		return $currentDeserializer;
	}

	public function testLegacySerializationWithoutId_legacyIsDetected() {
		$deserializer = new EntityDeserializer(
			$this->getStubLegacyDeserializer(),
			$this->getThrowingDeserializer()
		);

		$returnValue = $deserializer->deserialize( $this->getLegacyItemSerializationWithoutId() );

		$this->assertEquals( 'legacy', $returnValue );
	}

	private function getLegacyItemSerializationWithoutId() {
		return array( 'aliases' => array(
			'en' => array( 'foo', 'bar' )
		) );
	}

	public function testCurrentSerializationWithoutId_currentIsDetected() {
		$deserializer = new EntityDeserializer(
			$this->getThrowingDeserializer(),
			$this->getStubCurrentDeserializer()
		);

		$returnValue = $deserializer->deserialize( $this->getCurrentItemSerializationWithoutId() );

		$this->assertEquals( 'current', $returnValue );
	}

	private function getCurrentItemSerializationWithoutId() {
		return array( 'aliases' => array(
			'en' => array(
				array(
					'language' => 'en',
					'value' => 'foo',
				),
				array(
					'language' => 'en',
					'value' => 'bar',
				),
			)
		) );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$deserializer = new EntityDeserializer(
			$this->getThrowingDeserializer(),
			$this->getThrowingDeserializer()
		);

		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 5 ),
			array( array() ),
			array( array( 'entity' => 'P42', 'datatype' => null ) ),
		);
	}

}
