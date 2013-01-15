<?php

namespace Wikibase\Test;
use \Wikibase\Property;

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
 * @group WikibaseLib
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
	 * @return \Wikibase\Property
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

	public function testGetDataType() {
		$property = $this->getNewEmpty();

		$pokemons = null;

		try {
			$property->getDataType();
		}
		catch ( \Exception $pokemons ) {}

		$this->assertInstanceOf( '\MWException', $pokemons );

		foreach ( \Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
			$dataType = $libRegistry->getDataTypeFactory()->getType( $dataTypeId );

			$property->setDataType( $dataType );

			$this->assertInstanceOf( '\DataTypes\DataType', $property->getDataType() );
		}
	}

	public function testSetDataType() {
		$property = $this->getNewEmpty();

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$dataTypeFactory = $libRegistry->getDataTypeFactory();

		foreach ( \Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$dataType = $dataTypeFactory->getType( $dataTypeId );

			$property->setDataType( $dataType );

			$this->assertEquals( $dataType, $property->getDataType() );
		}
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
		$entity->addClaim( new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak(
			new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 )
		) ) );
		$objects[] = $entity;

		return $this->arrayWrap( $objects );
	}

	public function newDataValueProvider() {
		$argLists = array();

		$property = \Wikibase\Property::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 42 ) );
		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$property->setDataType( $libRegistry->getDataTypeFactory()->getType( 'commonsMedia' ) );

		return $argLists;
	}

	/**
	 * @dataProvider newDataValueProvider
	 *
	 * @param \Wikibase\Property $property
	 * @param \DataValues\DataValue $dataValue
	 */
	public function testNewDataValue( Property $property, \DataValues\DataValue $dataValue ) {
		$newDataValue = $property->newDataValue( $dataValue->getArrayValue() );

		$this->assertTrue( $dataValue->equals( $newDataValue ) );
	}

}