<?php

namespace Wikibase\Test;
use \Wikibase\PropertyObject;
use \Wikibase\Property;

/**
 * Tests for the Wikibase\PropertyObject class.
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
 * @group PropertyObjectTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Property
	 */
	protected function getNewEmpty() {
		return PropertyObject::newEmpty();
	}

	/**
	 * @see   EntityObjectTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return PropertyObject::newFromArray( $data );
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
			$dataType = \DataTypes\DataTypeFactory::singleton()->getType( $dataTypeId );

			$property->setDataType( $dataType );

			$this->assertInstanceOf( '\DataTypes\DataType', $property->getDataType() );
		}
	}

	public function testSetDataType() {
		$property = $this->getNewEmpty();

		foreach ( \Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$dataType = \DataTypes\DataTypeFactory::singleton()->getType( $dataTypeId );

			$property->setDataType( $dataType );

			$this->assertEquals( $dataType, $property->getDataType() );
		}
	}

	public function testSetDataTypeById() {
		$property = $this->getNewEmpty();

		foreach ( \Wikibase\Settings::get( 'dataTypes' ) as $dataTypeId ) {
			$property->setDataTypeById( $dataTypeId );
			$this->assertEquals( $dataTypeId, $property->getDataType()->getId() );
		}

		$pokemons = null;

		try {
			$property->setDataTypeById( 'this-does-not-exist' );
		}
		catch ( \Exception $pokemons ) {}

		$this->assertInstanceOf( '\MWException', $pokemons );
	}

	public function propertyProvider() {
		$objects = array();

		$objects[] = PropertyObject::newEmpty();

		$entity = PropertyObject::newEmpty();
		$entity->setDescription( 'en', 'foo' );
		$objects[] = $entity;

		$entity = PropertyObject::newEmpty();
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

	/**
	 * @dataProvider itemProvider
	 *
	 * @param Property $property
	 */
	public function testHasStatements( Property $property ) {
		$has = $property->hasClaims();
		$this->assertInternalType( 'boolean', $has );

		$this->assertEquals( count( $property->getClaims() ) !== 0, $has );
	}

	public function newDataValueProvider() {
		$argLists = array();

		$property = \Wikibase\PropertyObject::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new \DataValues\NumberValue( 42 ) );
		$argLists[] = array( clone $property, new \DataValues\NumberValue( 9001 ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new \DataValues\NumberValue( 9001 ) );

		$property->setDataType( \DataTypes\DataTypeFactory::singleton()->getType( 'commonsMedia' ) );

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