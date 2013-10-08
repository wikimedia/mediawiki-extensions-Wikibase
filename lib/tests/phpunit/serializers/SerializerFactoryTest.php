<?php

namespace Wikibase\Lib\Test\Serializers;

use Wikibase\Claim;
use Wikibase\Item;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\PropertyNoValueSnak;
use Wikibase\Reference;

/**
 * @covers Wikibase\Lib\Serializers\SerializerFactory
 *
 * @since 0.4
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SerializerFactoryTest extends \MediaWikiTestCase {

	/**
	 * @since 0.5
	 *
	 * @return SerializationOptions
	 */
	protected function getSerializationOptions() {
		$dataTypeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'test' ) );

		$options = new SerializationOptions( $dataTypeLookup );
		return $options;
	}

	public function testConstructor() {
		new SerializerFactory( $this->getSerializationOptions() );
		$this->assertTrue( true );
	}

	public function objectProvider() {
		$argLists = array();

		$argLists[] = array( new PropertyNoValueSnak( 42 ) );
		$argLists[] = array( new Reference() );
		$argLists[] = array( new Claim( new PropertyNoValueSnak( 42 ) ) );

		$argLists[] = array( Item::newEmpty(), new EntitySerializationOptions() );

		return $argLists;
	}

	/**
	 * @dataProvider objectProvider
	 */
	public function testNewSerializerForObject( $object, $options = null ) {
		$factory = new SerializerFactory( $this->getSerializationOptions() );

		$serializer = $factory->newSerializerForObject( $object, $options );

		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\Serializer', $serializer );

		$serializer->getSerialized( $object );
	}

	public function serializationProvider() {
		$argLists = array();

		$snak = new PropertyNoValueSnak( 42 );

		$factory = new SerializerFactory( $this->getSerializationOptions() );
		$serializer = $factory->newSerializerForObject( $snak );

		$argLists[] = array( 'Wikibase\Snak', $serializer->getSerialized( $snak ) );

		return $argLists;
	}

	/**
	 * @dataProvider serializationProvider
	 *
	 * @param string $className
	 * @param array $serialization
	 */
	public function testNewUnserializerForClass( $className, array $serialization ) {
		$factory = new SerializerFactory( $this->getSerializationOptions() );

		$unserializer = $factory->newUnserializerForClass( $className );

		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\Unserializer', $unserializer );

		$unserializer->newFromSerialization( $serialization );
	}

}
