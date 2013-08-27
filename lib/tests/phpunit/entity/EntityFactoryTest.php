<?php

namespace Wikibase\Test;
use Wikibase\EntityFactory;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Query;

/**
 * Tests for the Wikibase\EntityFactory class.
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
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityFactoryTest extends EntityTestCase {

	public function testGetEntityTypes() {
		$types = EntityFactory::singleton()->getEntityTypes();
		$this->assertInternalType( 'array', $types );

		$this->assertTrue( in_array( Item::ENTITY_TYPE, $types ), "must contain item type" );
		$this->assertTrue( in_array( Property::ENTITY_TYPE, $types ), "must contain property type" );

		// TODO
		// $this->assertTrue( in_array( Query::ENTITY_TYPE, $types ), "must contain query type" );
	}

	public static function provideIsEntityType() {
		$types = EntityFactory::singleton()->getEntityTypes();

		$tests = array();

		foreach ( $types as $type ) {
			$tests[] = array ( $type, true );
		}

		$tests[] = array ( 'this-does-not-exist', false );

		return $tests;
	}

	/**
	 * @dataProvider provideIsEntityType
	 */
	public function testIsEntityType( $type, $expected ) {
		$this->assertEquals( $expected, EntityFactory::singleton()->isEntityType( $type ) );
	}

	public static function provideNewEmpty() {
		return array(
			array( Item::ENTITY_TYPE, '\Wikibase\Item' ),
			array( Property::ENTITY_TYPE, '\Wikibase\Property' ),

			// TODO
			//array( Query::ENTITY_TYPE, '\Wikibase\Query' ),
		);
	}

	/**
	 * @dataProvider provideNewEmpty
	 */
	public function testNewEmpty( $type, $class ) {
		$entity = EntityFactory::singleton()->newEmpty( $type );

		$this->assertInstanceOf( $class, $entity );
		$this->assertTrue( $entity->isEmpty(), "should be empty" );
	}

	public static function provideNewFromArray() {
		return array(
			array( // #0
				Item::ENTITY_TYPE,
				array(
					'label' => array(
						'en' => 'Foo',
						'de' => 'FOO',
					)
				),
				'\Wikibase\Item' ),
		);
	}

	/**
	 * @dataProvider provideNewFromArray
	 */
	public function testNewFromArray( $type, $data, $class ) {
		$entity = EntityFactory::singleton()->newFromArray( $type, $data );

		$this->assertInstanceOf( $class, $entity );
		$this->assertEntityStructureEquals( $data, $entity );
	}

	/**
	 * @param Entity|array $expected
	 * @param Entity|array $actual
	 * @param String|null  $message
	 *
	 * @todo factor this out so it can be reused by EntityContentFactoryTest, etc.
	 */
	protected function assertEntityStructureEquals( $expected, $actual, $message = null ) {
		if ( $expected instanceof Entity ) {
			$expected = $expected->toArray();
		}

		if ( $actual instanceof Entity ) {
			$actual = $actual->toArray();
		}

		$keys = array_unique( array_merge(
			array_keys( $expected ),
			array_keys( $actual ) ) );

		foreach ( $keys as $k ) {
			if ( empty( $expected[ $k ] ) ) {
				if ( !empty( $actual[ $k ] ) ) {
					$this->fail( "$k should be empty; $message" );
				}
			} else {
				if ( empty( $actual[ $k ] ) ) {
					$this->fail( "$k should not be empty; $message" );
				}

				$this->assertArrayEquals( $expected[ $k ], $actual[ $k ], false, true );
			}
		}
	}
}
