<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;

/**
 * Tests for the Wikibase\EntityId class.
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
 * @since 0.3
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityIdTest extends \PHPUnit_Framework_TestCase {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( Item::ENTITY_TYPE, 123 );
		$argLists[] = array( Property::ENTITY_TYPE, 321 );

		return $argLists;
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param string $type
	 * @param integer $number
	 */
	public function testConstructor( $type, $number ) {
		$id = new EntityId( $type, $number );

		$this->assertEquals( $type, $id->getEntityType() );
		$this->assertEquals( $number, $id->getNumericId() );
	}

	public function instanceProvider() {
		$ids = array();

		foreach ( $this->constructorProvider() as $argList ) {
			$ids[] = array( new EntityId( $argList[0], $argList[1] ) );
		}

		return $ids;
	}

	public function equalityProvider() {
		$argLists = array();

		$types = array(
			Item::ENTITY_TYPE,
			Property::ENTITY_TYPE,
		);

		foreach ( array_values( $types ) as $type ) {
			$id = new EntityId( $type, 42 );

			foreach ( array( 1, 42, 9001 ) as $numericId ) {
				foreach ( $types as $secondType ) {
					$secondId = new EntityId( $secondType, $numericId );

					$matches = $type === $secondType && $numericId === 42;

					$argLists[] = array( $id, $secondId, $matches );
				}
			}
		}

		return $argLists;
	}

	/**
	 * @dataProvider equalityProvider
	 */
	public function testEquals( EntityId $id0, EntityId $id1, $expectedEquals ) {
		$this->assertEquals( $expectedEquals, $id0->equals( $id1 ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityId $id
	 */
	public function testEqualsSimple( EntityId $id ) {
		$this->assertTrue( $id->equals( $id ) );
		$this->assertFalse( $id->equals( $id->getNumericId() ) );
		$this->assertFalse( $id->equals( $id->getEntityType() ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityId $id
	 */
	public function testSerializationRoundtrip( EntityId $id ) {
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	public function testDeserializationCompatibility() {
		$v04serialization = 'C:17:"Wikibase\EntityId":12:{["item",123]}';

		$this->assertEquals(
			new EntityId(
				'item',
				123
			),
			unserialize( $v04serialization )
		);
	}

	public function testSerializationStability() {
		$v04serialization = 'C:17:"Wikibase\EntityId":12:{["item",123]}';
		$id = new EntityId(
			'item',
			123
		);

		$this->assertEquals(
			serialize( $id ),
			$v04serialization
		);
	}

}
