<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;

/**
 * @covers WikibaseLib.entitytypes.php
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypeRegistryTest extends PHPUnit_Framework_TestCase {

	private function getRegistry() {
		return require __DIR__  . '/../../WikibaseLib.entitytypes.php';
	}

	private function getSerializerFactroy( $entityType ) {
		$serializerFactory = $this->getMockBuilder( 'Wikibase\DataModel\SerializerFactory' )
			->disableOriginalConstructor()
			->getMock();

		$serializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Serializer' )
			->will( $this->returnValue( $this->getMock( 'Serializers\Serializer' ) ) );

		return $serializerFactory;
	}

	private function getDeserializerFactroy( $entityType ) {
		$deserializerFactory = $this->getMockBuilder( 'Wikibase\DataModel\DeserializerFactory' )
			->disableOriginalConstructor()
			->getMock();

		$deserializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Deserializer' )
			->will( $this->returnValue( $this->getMock( 'Deserializers\Deserializer' ) ) );

		return $deserializerFactory;
	}

	public function provideEntityTypes() {
		return array(
			array( 'item' ),
			array( 'property' )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testSerializerFactory( $entityType ) {
		$registry = $this->getRegistry()[$entityType];
		$serializerFactory = $this->getSerializerFactroy( $entityType );

		$this->assertInstanceOf(
			'Serializers\Serializer',
			call_user_func( $registry['serializer-factory-callback'], $serializerFactory )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testDeserializerFactory( $entityType ) {
		$registry = $this->getRegistry()[$entityType];
		$deserializerFactroy = $this->getDeserializerFactroy( $entityType );

		$this->assertInstanceOf(
			'Deserializers\Deserializer',
			call_user_func( $registry['deserializer-factory-callback'], $deserializerFactroy )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testChangeSerializer( $entityType ) {
		$registry = $this->getRegistry()[$entityType];

		$this->assertInstanceOf(
			'Wikibase\EntityChange',
			call_user_func( $registry['change-factory-callback'], array() )
		);
	}

}
