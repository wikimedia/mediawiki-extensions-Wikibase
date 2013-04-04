<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use Wikibase\EntityId;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\SnaksFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;
use Wikibase\Test\MockRepository;

/**
 * Tests for the Wikibase\Lib\SnaksFormatter class.
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
 * @group SnaksFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnaksFormatterTest extends \PHPUnit_Framework_TestCase {

	private $stringPropertyId = 42;

	private function newPropertyDataTypeLookup() {
		$lookup = new InMemoryDataTypeLookup();

		$lookup->setDataTypeForProperty(
			new EntityId( Property::ENTITY_TYPE, $this->stringPropertyId ),
			'string-datatype'
		);

		return $lookup;
	}

	private function newFormatter() {
		return new SnaksFormatter(
			$this->newPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			DataTypeFactory::newFromTypes( array(
				$this->newStringDataType()
		 	) )
		);
	}

	private function newStringDataType() {
		return new DataType(
			'string-datatype',
			'string',
			array(),
			array(),
			array()
		);
	}

	public function testFormatNoSnaks() {
		$formatted = $this->newFormatter()->formatSnaks( array() );

		$this->assertInternalType( 'array', $formatted );
		$this->assertEmpty( $formatted );
	}

	private function getStrings() {
		return array(
			'',
			'foo BAR baz!  ',
			'~=[,,_,,]:3'
		);
	}

	public function stringProvider() {
		$argLists = array();

		foreach ( $this->getStrings() as $string ) {
			$argLists[] = array( $string );
		}

		return $argLists;
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testFormatOnePropertyValueSnakWithString( $expected ) {
		$propertyValueSnak = new PropertyValueSnak(
			$this->stringPropertyId,
			new StringValue( $expected )
		);

		$formatted = $this->newFormatter()->formatSnaks( array( $propertyValueSnak ) );
		$this->assertFormatSnaksReturnType( $formatted );
		$this->assertCount( 1, $formatted );

		$actual = $formatted[0];
		$this->assertEquals( $expected, $actual );
	}

	private function assertFormatSnaksReturnType( $returnValue ) {
		$this->assertInternalType( 'array', $returnValue );
		$this->assertContainsOnly( 'string', $returnValue );
	}

	public function testFormatMultipleValues() {
		$propertyValueSnaks = array();

		foreach ( $this->getStrings() as $string ) {
			$propertyValueSnaks[] = new PropertyValueSnak(
				$this->stringPropertyId,
				new StringValue( $string )
			);
		}

		$formatted = $this->newFormatter()->formatSnaks( $propertyValueSnaks );
		$this->assertFormatSnaksReturnType( $formatted );
		$this->assertSameSize( $this->getStrings(), $formatted );
		$this->assertEquals( $this->getStrings(), $formatted );


		// TODO: test with more then one property id
	}

	// TODO: test non-string types, esp those with formatters

}
