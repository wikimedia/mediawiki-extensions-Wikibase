<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;

/**
 * Tests for the Wikibase\Property class.
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
 * @group WikibaseProperty
 * @group WikibaseDataModel
 * @group PropertyTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTest extends EntityTest {

	/**
	 * @see EntityTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	protected function getNewEmpty() {
		return Property::newEmpty();
	}

	/**
	 * @see   EntityTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return Property::newFromArray( $data );
	}

	public function propertyProvider() {
		$objects = array();

		$objects[] = Property::newEmpty();

		$entity = Property::newEmpty();
		$entity->setDescription( 'en', 'foo' );
		$objects[] = $entity;

		$entity = Property::newEmpty();
		$entity->setDescription( 'en', 'foo' );
		$entity->setDescription( 'de', 'foo' );
		$entity->setLabel( 'en', 'foo' );
		$entity->setAliases( 'de', array( 'bar', 'baz' ) );
		$objects[] = $entity;

		$entity = $entity->copy();
		$entity->addClaim( new Claim( new PropertyNoValueSnak(
			new EntityId( Property::ENTITY_TYPE, 42 )
		) ) );
		$objects[] = $entity;

		return $this->arrayWrap( $objects );
	}

	public function newDataValueProvider() {
		$argLists = array();

		$property = Property::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new EntityId( Item::ENTITY_TYPE, 42 ) );
		$argLists[] = array( clone $property, new EntityId( Item::ENTITY_TYPE, 9001 ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new EntityId( Item::ENTITY_TYPE, 9001 ) );

		$property->setDataTypeId( 'commonsMedia' );

		return $argLists;
	}

	/**
	 * @dataProvider newDataValueProvider
	 *
	 * @param Property $property
	 * @param \DataValues\DataValue $dataValue
	 */
	public function testNewDataValue( Property $property, \DataValues\DataValue $dataValue ) {
		$newDataValue = $property->newDataValue( $dataValue->getArrayValue() );

		$this->assertTrue( $dataValue->equals( $newDataValue ) );
	}

	public function testNewFromType() {
		$property = Property::newFromType( 'string' );
		$this->assertInstanceOf( 'Wikibase\Property', $property );
		$this->assertEquals( 'string', $property->getDataTypeId() );
	}

	public function testSetAndGetDataTypeId() {
		$property = Property::newFromType( 'string' );

		foreach ( array( 'string', 'foobar', 'nyan', 'string' ) as $typeId ) {
			$property->setDataTypeId( $typeId );
			$this->assertEquals( $typeId, $property->getDataTypeId() );
		}
	}

}