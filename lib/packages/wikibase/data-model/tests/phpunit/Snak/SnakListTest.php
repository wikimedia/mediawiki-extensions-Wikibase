<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;
use Wikibase\SnakList;

/**
 * Tests for the Wikibase\SnakList class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakListTest extends HashArrayTest {

	/**
	 * @see GenericArrayObjectTest::getInstanceClass
	 */
	public function getInstanceClass() {
		return '\Wikibase\SnakList';
	}

	/**
	 * @see GenericArrayObjectTest::elementInstancesProvider
	 */
	public function elementInstancesProvider() {
		$id42 = new EntityId( Property::ENTITY_TYPE, 42 );

		$argLists = array();

		$argLists[] = array( array( new PropertyNoValueSnak( $id42 ) ) );
		$argLists[] = array( array( new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 9001 ) ) ) );
		$argLists[] = array( array( new PropertyValueSnak( $id42, new StringValue( 'a' ) ) ) );

		return $argLists;
	}

	public function constructorProvider() {
		$id42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$id9001 = new EntityId( Property::ENTITY_TYPE, 9001 );

		return array(
			array(),
			array( array() ),
			array( array(
				new PropertyNoValueSnak( $id42 )
			) ),
			array( array(
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
			) ),
			array( array(
				new PropertyNoValueSnak( $id42 ),
				new PropertyNoValueSnak( $id9001 ),
				new PropertyValueSnak( $id42, new StringValue( 'a' ) ),
			) ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testHasSnak( SnakList $array ) {
		/**
		 * @var Snak $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasSnak( $hashable ) );
			$this->assertTrue( $array->hasSnakHash( $hashable->getHash() ) );
			$array->removeSnak( $hashable );
			$this->assertFalse( $array->hasSnak( $hashable ) );
			$this->assertFalse( $array->hasSnakHash( $hashable->getHash() ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testRemoveSnak( SnakList $array ) {
		$elementCount = $array->count();

		/**
		 * @var Snak $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasSnak( $element ) );

			if ( $elementCount % 2 === 0 ) {
				$array->removeSnak( $element );
			}
			else {
				$array->removeSnakHash( $element->getHash() );
			}

			$this->assertFalse( $array->hasSnak( $element ) );
			$this->assertEquals( --$elementCount, $array->count() );
		}

		$element = new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) );

		$array->removeSnak( $element );
		$array->removeSnakHash( $element->getHash() );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\SnakList $array
	 */
	public function testAddSnak( SnakList $array ) {
		$elementCount = $array->count();

		$elements = $this->elementInstancesProvider();
		$element = array_shift( $elements );
		$element = $element[0][0];

		if ( !$array->hasSnak( $element ) ) {
			++$elementCount;
		}

		$this->assertEquals( !$array->hasSnak( $element ), $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );

		$this->assertFalse( $array->addSnak( $element ) );

		$this->assertEquals( $elementCount, $array->count() );
	}

	/**
	 * @dataProvider instanceProvider
	 * 
	 * @param SnakList $snaks
	 */
	public function testToArrayRoundtrip( SnakList $snaks ) {
		$serialization = serialize( $snaks->toArray() );
		$array = $snaks->toArray();

		$this->assertInternalType( 'array', $array, 'toArray should return array' );

		foreach ( array( $array, unserialize( $serialization ) ) as $data ) {
			$copy = SnakList::newFromArray( $data );

			$this->assertInstanceOf( '\Wikibase\Snaks', $copy, 'newFromArray should return object implementing Snaks' );

			$this->assertTrue( $snaks->equals( $copy ), 'getArray newFromArray roundtrip should work' );
		}
	}

}
