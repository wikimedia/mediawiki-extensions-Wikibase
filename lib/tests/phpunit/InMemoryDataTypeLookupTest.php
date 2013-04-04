<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Property;

/**
 * Tests for the Wikibase\Lib\InMemoryDataTypeLookup class.
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
 * @group DataTypeLookupTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryDataTypeLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetDataTypeForPropertyThatIsNotSet() {
		$lookup = new InMemoryDataTypeLookup();

		$this->setExpectedException( 'OutOfBoundsException' );

		$lookup->getDataTypeIdForProperty( new EntityId( Property::ENTITY_TYPE, 7201010 ) );
	}

	public function testSetAndGetDataType() {
		$propertyId = new EntityId( Property::ENTITY_TYPE, 7201010 );

		$stringTypeId = 'string-datatype';
		$intTypeId = 'integer';

		$lookup = new InMemoryDataTypeLookup();
		$lookup->setDataTypeForProperty( $propertyId, $stringTypeId );

		$actual = $lookup->getDataTypeIdForProperty( $propertyId );

		$this->assertInternalType( 'string', $actual );

		$this->assertEquals( $stringTypeId, $actual );

		$lookup->setDataTypeForProperty( $propertyId, $intTypeId );

		$actual = $lookup->getDataTypeIdForProperty( $propertyId );

		$this->assertNotEquals( $stringTypeId, $actual );
		$this->assertEquals( $intTypeId, $actual );
	}

	public function testSetWithItemId() {
		$lookup = new InMemoryDataTypeLookup();

		$this->setExpectedException( 'InvalidArgumentException' );

		$lookup->setDataTypeForProperty(
			new EntityId( Item::ENTITY_TYPE, 42 ),
			'string-datatype'
		);
	}

}
