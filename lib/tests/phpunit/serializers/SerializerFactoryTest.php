<?php

namespace Wikibase\Lib\Test\Serializers;

use Wikibase\Claim;
use Wikibase\Item;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
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
 */
class SerializerFactoryTest extends \MediaWikiTestCase {

	public function testConstructor() {
		new SerializerFactory();
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
		$factory = new SerializerFactory();

		$serializer = $factory->newSerializerForObject( $object, $options );

		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\Serializer', $serializer );

		$serializer->getSerialized( $object );
	}

	public function serializationProvider() {
		$argLists = array();

		$snak = new PropertyNoValueSnak( 42 );

		$factory = new SerializerFactory();
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
		$factory = new SerializerFactory();

		$unserializer = $factory->newUnserializerForClass( $className );

		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\Unserializer', $unserializer );

		$unserializer->newFromSerialization( $serialization );
	}

}
