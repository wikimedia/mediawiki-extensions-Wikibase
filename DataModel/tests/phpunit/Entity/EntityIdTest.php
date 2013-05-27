<?php

namespace Wikibase\Test;

use Wikibase\EntityId;

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

		$argLists[] = array( \Wikibase\Item::ENTITY_TYPE, 123 );
		$argLists[] = array( \Wikibase\Property::ENTITY_TYPE, 321 );

		// TODO
		//$argLists[] = array( \Wikibase\Query::ENTITY_TYPE, 9342 );

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
			$ids[] = array( new EntityId( $argList[0], $argList[1] ), $argList );
		}

		return $ids;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityId $id
	 * @param array $constructorArgs
	 */
	public function testGetEntityType( EntityId $id, array $constructorArgs ) {
		$this->assertEquals( $constructorArgs[0], $id->getEntityType() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityId $id
	 * @param array $constructorArgs
	 */
	public function testGetNumericId( EntityId $id, array $constructorArgs ) {
		$this->assertEquals( $constructorArgs[1], $id->getNumericId() );
	}

	public function equalityProvider() {
		$argLists = array();

		$types = array(
			\Wikibase\Item::ENTITY_TYPE,
			\Wikibase\Property::ENTITY_TYPE,

			// TODO
			// \Wikibase\Query::ENTITY_TYPE
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
	 * @param array $constructorArgs
	 */
	public function testEqualsSimple( EntityId $id, array $constructorArgs ) {
		$this->assertTrue( $id->equals( $id ) );
		$this->assertFalse( $id->equals( $id->getNumericId() ) );
		$this->assertFalse( $id->equals( $id->getEntityType() ) );
	}

}
