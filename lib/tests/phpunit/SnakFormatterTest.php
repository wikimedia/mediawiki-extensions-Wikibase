<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;

/**
 * Tests for the Wikibase\Lib\SnakFormatter class.
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
 * @group SnakFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakFormatterTest extends \PHPUnit_Framework_TestCase {

	private $stringPropertyId = 572106;

	private $itemPropertyId = 1730;

	private function newPropertyDataTypeLookup() {
		$lookup = new InMemoryDataTypeLookup();

		$lookup->setDataTypeForProperty(
			new EntityId( Property::ENTITY_TYPE, $this->stringPropertyId ),
			'string-datatype'
		);

		$lookup->setDataTypeForProperty(
			new EntityId( Property::ENTITY_TYPE, $this->itemPropertyId ),
			'wikibase-item-datatype'
		);

		return $lookup;
	}

	private function newFormatter() {
		return new SnakFormatter(
			$this->newPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			DataTypeFactory::newFromTypes( array(
				$this->newStringDataType(),
				$this->newItemDataType()
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

	private function newItemDataType() {
		$formatterOptions = new FormatterOptions( array(
			EntityIdParser::OPT_PREFIX_MAP => array(
				Item::ENTITY_TYPE => 'q'
			)
		) );

		$parserOptions = new ParserOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array(
				'q' => Item::ENTITY_TYPE
			)
		) );

		return new DataType(
			'wikibase-item-datatype',
			'wikibase-entityid',
			array( new EntityIdParser( $parserOptions ) ),
			array( new EntityIdFormatter( $formatterOptions ) ),
			array()
		);
	}

	public function testFormatNoSnaks() {
		$formatted = $this->newFormatter()->formatSnaks( array(), 'en' );

		$this->assertInternalType( 'array', $formatted );
		$this->assertEmpty( $formatted );
	}

	/**
	 * @dataProvider formatPropertyNotFoundProvider
	 */
	public function testFormatSnaksPropertyNotFound( $snak ) {
		$formatter = $this->newFormatter();

		$this->assertEquals( array( '' ), $formatter->formatSnaks( array( $snak ), 'de' ) );
	}

	public function formatPropertyNotFoundProvider() {
		return array(
			array(
				new PropertyValueSnak(
					new EntityId( Property::ENTITY_TYPE, 9000 ),
					new StringValue( 'xyz' )
				)
			)
		);
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

		$formatted = $this->newFormatter()->formatSnaks( array( $propertyValueSnak ), 'en' );
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

		$propertyValueSnaks[] = new PropertyValueSnak(
			$this->itemPropertyId,
			new EntityId( Item::ENTITY_TYPE, 1337 )
		);

		$formatted = $this->newFormatter()->formatSnaks( $propertyValueSnaks, 'en' );
		$this->assertFormatSnaksReturnType( $formatted );

		$expected = array_merge(
			$this->getStrings(),
			array( 'q1337' )
		);

		$this->assertSameSize( $expected, $formatted );
		$this->assertEquals( $expected, $formatted );
	}

}
