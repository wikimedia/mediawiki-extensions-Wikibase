<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\SnakConstructionService;
use Wikibase\Property;
use Wikibase\SnakFactory;

/**
 * Tests for the Wikibase\Lib\SnakConstructionService class.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Snak
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @covers Wikibase\Lib\SnakConstructionService
 */
class SnakConstructionServiceTest extends \PHPUnit_Framework_TestCase {

	public function newSnakConstructionService() {
		$snakFactory = new SnakFactory();
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeFactory = new DataTypeFactory();
		$dataValueFactory = DataValueFactory::singleton();

		$dataTypeFactory->registerDataType( new DataType( 'string', 'string', array(), array(), array() ) );
		$dataTypeLookup->setDataTypeForProperty( new EntityId( Property::ENTITY_TYPE, 1 ), 'string' );

		$service = new SnakConstructionService(
			$snakFactory,
			$dataTypeLookup,
			$dataTypeFactory,
			$dataValueFactory );

		return $service;
	}

	/**
	 * @dataProvider newSnakProvider
	 *
	 * @param $propertyId
	 * @param $snakType
	 * @param $rawValue
	 * @param $expectedSnakClass
	 * @param $expectedValue
	 * @param $expectedException
	 */
	public function testNewSnak( $propertyId, $snakType, $rawValue, $expectedSnakClass, $expectedValue, $expectedException ) {
		if ( is_int( $propertyId ) ) {
			$propertyId = new EntityId( Property::ENTITY_TYPE, $propertyId );
		}

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$service = $this->newSnakConstructionService();

		$snak = $service->newSnak( $propertyId, $snakType, $rawValue );

		$this->assertInstanceOf( $expectedSnakClass, $snak );

		if ( $expectedValue ) {
			$this->assertEmpty( $expectedValue, $snak->getValue() );
		}
	}

	public function newSnakProvider() {
		return array(
			'bad id' => array( new EntityId( Item::ENTITY_TYPE, 1 ), 'novalue', null, 'Wikibase\PropertyNoValueSnak', null, 'InvalidArgumentException' ),

			'novalue' => array( 1, 'novalue', null, 'Wikibase\PropertyNoValueSnak', null, null ),
			'somevalue' => array( 1, 'somevalue', null, 'Wikibase\PropertySomeValueSnak', null, null ),
			'value' => array( 1, 'value', '"hello"', 'Wikibase\PropertyValueSnak', null, null ),

			'novalue/badprop' => array( 66, 'novalue', null, 'Wikibase\PropertyNoValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),
			'somevalue/badprop' => array( 66, 'somevalue', null, 'Wikibase\PropertySomeValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),
			'value/badprop' => array( 66, 'value', '"hello"', 'Wikibase\PropertyValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),

			'value/badvalue' => array( 1, 'value', array( "foo" ), 'Wikibase\PropertyValueSnak', null, 'DataValues\IllegalValueException' ),
		);
	}

}
