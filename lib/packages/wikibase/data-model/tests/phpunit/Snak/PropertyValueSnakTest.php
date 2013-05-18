<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\PropertyValueSnak;

/**
 * Tests for the Wikibase\PropertyValueSnak class.
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
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		$argLists = array(
			array( true, 1, new StringValue( 'a' ) ),
			array( true, 9001, new StringValue( 'a' ) ),
		);

		foreach ( $argLists as &$argList ) {
			if ( count( $argList ) > 1 ) {
				$argList[1] = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, $argList[1] );
			}
		}

		return $argLists;
	}

	public function getClass() {
		return '\Wikibase\PropertyValueSnak';
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataValue( PropertyValueSnak $omnomnom ) {
		$dataValue = $omnomnom->getDataValue();
		$this->assertInstanceOf( '\DataValues\DataValue', $dataValue );
		$this->assertTrue( $dataValue->equals( $omnomnom->getDataValue() ) );
	}

	public function newFromPropertyValueProvider() {
		$argLists = array();

		$property = \Wikibase\Property::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 42 ) );
		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$property->setDataType( $libRegistry->getDataTypeFactory()->getType( 'commonsMedia' ) );

		$argLists[] = array( clone $property, new \DataValues\StringValue( 'https://commons.wikimedia.org/wiki/Wikidata' ) );

		return $argLists;
	}

	/**
	 * @dataProvider newFromPropertyValueProvider
	 */
	public function testNewFromPropertyValue( \Wikibase\Property $property, \DataValues\DataValue $dataValue ) {
		if ( !class_exists( '\Wikibase\PropertyContent' ) ) {
			$this->markTestSkipped( 'PropertyContent class not found' );
		}

		// We need to make sure the property exists since otherwise
		// we cannot obtain it based on id in the method being tested.
		$content = \Wikibase\PropertyContent::newFromProperty( $property );
		$content->save();

		$instance = PropertyValueSnak::newFromPropertyValue(
			$property->getId(),
			$dataValue->getArrayValue()
		);

		$this->assertInstanceOf( '\Wikibase\PropertyValueSnak', $instance );
		$this->assertTrue( $instance->getDataValue()->equals( $dataValue ) );
		$this->assertEquals( $property->getId(), $instance->getPropertyId() );
	}

}
