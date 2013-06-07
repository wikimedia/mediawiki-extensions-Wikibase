<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\MapValueHasher;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;

/**
 * @covers Wikibase\MapValueHasher
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
 * @since 0.1
 *
 * @ingroup WikibaseDataModel
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		new MapValueHasher( true );
		$this->assertTrue( true );
	}

	public function testHash() {
		$hasher = new MapValueHasher();

		$map0 = array(
			'foo' => new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 1 ) ),
			'bar' => new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 2 ) ),
			42 => new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) ),
			new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 9001 ) ),
		);

		$hash = $hasher->hash( $map0 );

		$map1 = $map0;
		unset( $map1['foo'] );
		$map1[] = $map0['foo'];

		$this->assertEquals( $hash, $hasher->hash( $map1 ) );

		$map4 = new \ArrayObject( $map0 );
		$this->assertEquals( $hash, $hasher->hash( $map4 ) );

		$map2 = $map0;
		unset( $map2['foo'] );

		$this->assertNotEquals( $hash, $hasher->hash( $map2 ) );

		$map3 = $map0;
		$map3['foo'] = new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 5 ) );

		$this->assertNotEquals( $hash, $hasher->hash( $map3 ) );
	}

	public function testHashThrowsExceptionOnInvalidArgument() {
		$hasher = new MapValueHasher();

		$this->setExpectedException( 'InvalidArgumentException' );
		$hasher->hash( null );
	}

}
