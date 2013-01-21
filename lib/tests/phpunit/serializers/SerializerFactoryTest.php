<?php

namespace Wikibase\Lib\Test\Serializers;
use Wikibase\Lib\Serializers\SerializerFactory;

/**
 * Tests for the Wikibase\Lib\Serializers\SerializerFactory class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerTest extends \MediaWikiTestCase {

	public function testConstructor() {
		new SerializerFactory();
		$this->assertTrue( true );
	}

	public function objectProvider() {
		$argLists = array();

		$argLists[] = array( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$argLists[] = array( new \Wikibase\Reference() );
		$argLists[] = array( new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak( 42 ) ) );
		$argLists[] = array( \Wikibase\Item::newEmpty() );
		$argLists[] = array( \Wikibase\Query::newEmpty() );

		return $argLists;
	}

	/**
	 * @dataProvider objectProvider
	 *
	 * @param mixed $object
	 */
	public function testNewSerializerForObject( $object ) {
		$factory = new SerializerFactory();

		$serializer = $factory->newSerializerForObject( $object );

		$this->assertInstanceOf( 'Wikibase\Lib\Serializers\Serializer', $serializer );

		$serializer->getSerialized( $object );
	}

	public function serializationProvider() {
		$argLists = array();

		$snak = new \Wikibase\PropertyNoValueSnak( 42 );

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
