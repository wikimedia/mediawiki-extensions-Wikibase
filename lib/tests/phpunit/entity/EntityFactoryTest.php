<?php

namespace Wikibase\Test;
use Wikibase\EntityFactory;

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
 */
class EntityFactoryTest extends \MediaWikiTestCase {

	public function provideGetIdParts() {
		$data = array();
		$numbers = array( '0', '1', '23' );
		$prefixes = EntityFactory::getEntityPrefixes();
		foreach ( $prefixes as $prefix ) {
			foreach ( $numbers as $num ) {
				$data[] = array( "{$num}", array( "{$num}", '', $num, '' ) );
				$data[] = array( "{$prefix}", array() );
				$data[] = array( "{$prefix}{$num}", array( "{$prefix}{$num}", $prefix, $num, '' ) );
				$data[] = array( "{$num}{$prefix}", array() );
				$data[] = array( "{$prefix}{$num}#foobar", array( "{$prefix}{$num}#foobar", $prefix, $num, '#foobar' ) );
				$data[] = array( "{$prefix}{$num}#", array( "{$prefix}{$num}#", $prefix, $num, '#' ) );
			}
		}
		return $data;
	}

	/**
	 * @dataProvider provideGetIdParts
	 */
	public function testGetIdParts( $id, $expect ) {

		$method = new \ReflectionMethod( 'Wikibase\EntityFactory', 'getIdParts' );
		$method->setAccessible( true );
		$parts = $method->invoke( EntityFactory::singleton(), $id );
		$this->assertEquals( $expect, $parts );
	}

	public function provideIsPrefixed() {
		$data = array();
		$numbers = array( '0', '1', '23', '456', '7890' );
		$prefixes = EntityFactory::getEntityPrefixes();
		foreach ( $prefixes as $prefix ) {
			foreach ( $numbers as $num ) {
				$data[] = array( "{$num}", false );
				$data[] = array( "{$prefix}", false );
				$data[] = array( "{$prefix}{$num}", true );
				$data[] = array( "{$num}{$prefix}", false );
			}
		}
		return $data;
	}

	/**
	 * @dataProvider provideIsPrefixed
	 */
	public function testIsPrefixed( $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->isPrefixedId( $id ) );
	}

	public function provideGetEntityTypeFromPrefixedId() {
		return array(
			array( \Wikibase\ItemObject::getIdPrefix() . '123', \Wikibase\Item::ENTITY_TYPE ),
			array( \Wikibase\QueryObject::getIdPrefix() . '123', \Wikibase\Query::ENTITY_TYPE ),
			array( \Wikibase\PropertyObject::getIdPrefix() . '123', \Wikibase\Property::ENTITY_TYPE ),
		);
	}

	/**
	 * @dataProvider provideGetEntityTypeFromPrefixedId
	 */
	public function testGetEntityTypeFromPrefixedId( $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->getEntityTypeFromPrefixedId( $id ) );
	}

	public function provideGetPrefixedId() {
		return array(
			array( \Wikibase\Item::ENTITY_TYPE, '123', \Wikibase\ItemObject::getIdPrefix() . '123' ),
			array( \Wikibase\Query::ENTITY_TYPE, '123', \Wikibase\QueryObject::getIdPrefix() . '123' ),
			array( \Wikibase\Property::ENTITY_TYPE, '123', \Wikibase\PropertyObject::getIdPrefix() . '123' ),
		);
	}

	/**
	 * @dataProvider provideGetPrefixedId
	 */
	public function testGetPrefixedId( $type, $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->getPrefixedId( $type, $id ) );
	}

	public function provideGetUnprefixedId() {
		return array(
			array( \Wikibase\ItemObject::getIdPrefix() . '123', 123 ),
			array( \Wikibase\QueryObject::getIdPrefix() . '123', 123 ),
			array( \Wikibase\PropertyObject::getIdPrefix() . '123', 123 ),
		);
	}

	/**
	 * @dataProvider provideGetUnprefixedId
	 */
	public function testGetUnprefixedId( $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->getUnprefixedId( $id ) );
	}

	public function provideGetFragment() {
		$data = array();
		$prefixes = EntityFactory::getEntityPrefixes();
		foreach ( $prefixes as $prefix ) {
			$data[] = array( $prefix . '123#foobar', 'foobar' );
			$data[] = array( $prefix . '123#', '' );
			$data[] = array( $prefix . '123', '' );
		}
		return $data;
	}

	/**
	 * @dataProvider provideGetFragment
	 */
	public function testGetIdFragment( $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->getIdFragment( $id ) );
	}

	public function testGetEntityTypes() {
		$this->assertInternalType( 'array', EntityFactory::singleton()->getEntityTypes() );
	}

	public function testIsEntityType() {
		foreach ( EntityFactory::singleton()->getEntityTypes() as $type ) {
			$this->assertTrue( EntityFactory::singleton()->isEntityType( $type ) );
		}

		$this->assertFalse( EntityFactory::singleton()->isEntityType( 'this-does-not-exist' ) );
	}

}
