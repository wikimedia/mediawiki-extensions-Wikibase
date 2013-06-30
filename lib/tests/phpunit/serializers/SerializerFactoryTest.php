<?php

namespace Wikibase\Lib\Test\Serializers;

use Wikibase\Claim;
use Wikibase\Item;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\PropertyNoValueSnak;
use Wikibase\Reference;

/**
 * @covers Wikibase\Lib\Serializers\SerializerFactory
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

		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()->getMock();
		$argLists[] = array( Item::newEmpty(), new EntitySerializationOptions( $idFormatter ) );

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
