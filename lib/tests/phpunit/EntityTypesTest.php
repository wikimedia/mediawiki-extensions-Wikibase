<?php

namespace Wikibase\Lib\Tests;

use Deserializers\Deserializer;
use PHPUnit_Framework_TestCase;
use Serializers\Serializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;

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

	/**
	 * @param string $entityType
	 *
	 * @return SerializerFactory
	 */
	private function getSerializerFactroy( $entityType ) {
		$serializerFactory = $this->getMockBuilder( SerializerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$serializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Serializer' )
			->will( $this->returnValue( $this->getMock( Serializer::class ) ) );

		return $serializerFactory;
	}

	/**
	 * @param string $entityType
	 *
	 * @return DeserializerFactory
	 */
	private function getDeserializerFactroy( $entityType ) {
		$deserializerFactory = $this->getMockBuilder( DeserializerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Deserializer' )
			->will( $this->returnValue( $this->getMock( Deserializer::class ) ) );

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

}
