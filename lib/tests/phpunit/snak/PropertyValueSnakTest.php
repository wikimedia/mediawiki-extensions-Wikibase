<?php

namespace Wikibase\Test;
use Wikibase\PropertyValueSnak as PropertyValueSnak;
use DataValues\StringValue;

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
 * @group WikibaseLib
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyValueSnakTest extends PropertySnakObjectTest {

	public function constructorProvider() {
		return array(
			array( true, 1, new StringValue( 'a' ) ),
			array( true, 9001, new StringValue( 'a' ) ),
			array( false ),
			array( false, 42 ),
		);
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
		$this->assertEquals( $dataValue, $omnomnom->getDataValue() );
	}

}
