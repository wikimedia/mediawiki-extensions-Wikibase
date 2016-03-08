<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;

/**
 * @covers WikibaseLib.entitytypes.php
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTypesTest extends PHPUnit_Framework_TestCase {

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
		return array_map(
			function( $entityType ) {
				return array( $entityType );
			},
			array_keys( $this->getRegistry() )
		);
	}

	public function testKnownEntityTypesSupported() {
		$entityTypes = $this->provideEntityTypes();

		$this->assertContains( array( 'item' ), $entityTypes );
		$this->assertContains( array( 'property' ), $entityTypes );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testSerializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$serializerFactory = $this->getSerializerFactroy( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( 'serializer-factory-callback', $registry[$entityType] );

		$callback = $registry[$entityType]['serializer-factory-callback'];

		$this->assertInternalType( 'callable', $callback );

		$this->assertInstanceOf(
			'Serializers\Serializer',
			call_user_func( $callback, $serializerFactory )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testDeserializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$deserializerFactroy = $this->getDeserializerFactroy( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( 'deserializer-factory-callback', $registry[$entityType] );

		$callback = $registry[$entityType]['deserializer-factory-callback'];

		$this->assertInternalType( 'callable', $callback );

		$this->assertInstanceOf(
			'Deserializers\Deserializer',
			call_user_func( $callback, $deserializerFactroy )
		);
	}

}
