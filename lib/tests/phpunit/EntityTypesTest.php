<?php

namespace Wikibase\Lib\Tests;

use Deserializers\Deserializer;
use PHPUnit_Framework_TestCase;
use Serializers\Serializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;

/**
 * @group Wikibase
 *
 * @license GNU GPL v2+
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
	private function getSerializerFactory( $entityType ) {
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
	private function getDeserializerFactory( $entityType ) {
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
				return [ $entityType ];
			},
			array_keys( $this->getRegistry() )
		);
	}

	public function testKnownEntityTypesSupported() {
		$entityTypes = $this->provideEntityTypes();

		$this->assertContains( [ 'item' ], $entityTypes );
		$this->assertContains( [ 'property' ], $entityTypes );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testSerializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$serializerFactory = $this->getSerializerFactory( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( 'serializer-factory-callback', $registry[$entityType] );

		$callback = $registry[$entityType]['serializer-factory-callback'];

		$this->assertInternalType( 'callable', $callback );

		$this->assertInstanceOf(
			Serializer::class,
			call_user_func( $callback, $serializerFactory )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testDeserializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$deserializerFactory = $this->getDeserializerFactory( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( 'deserializer-factory-callback', $registry[$entityType] );

		$callback = $registry[$entityType]['deserializer-factory-callback'];

		$this->assertInternalType( 'callable', $callback );

		$this->assertInstanceOf(
			Deserializer::class,
			call_user_func( $callback, $deserializerFactory )
		);
	}

}
