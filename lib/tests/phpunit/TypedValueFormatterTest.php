<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataValues\DataValue;
use DataValues\StringValue;
use ValueFormatters\ValueFormatterBase;
use Wikibase\Lib\TypedValueFormatter;

/**
 * Tests for the Wikibase\Lib\TypedValueFormatter class.
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
 * @group TypedValueFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function formatToStringProvider() {
		$stringType = new DataType(
			'string-of-doom',
			'string',
			array(),
			array(),
			array()
		);

		$stringValues = array(
			'',
			'foo',
			' foo ',
			'FoO',
			'foo bar baz!',
			'~=[,,_,,]:3 NyanData all the way across the sky! ~=[,,_,,]:3'
		);

		$argLists = array();

		foreach ( $stringValues as $stringValue ) {
			$argLists[] = array(
				new StringValue( $stringValue ),
				$stringType,
				$stringValue
			);
		}

		// TODO: test other types, esp ones with a formatter

		return $argLists;
	}

	/**
	 * @dataProvider formatToStringProvider
	 */
	public function testFormatToString( DataValue $input, DataType $type, $expected ) {
		$formatter = new TypedValueFormatter();

		$actual = $formatter->formatToString( $input, $type, 'en' );

		$this->assertInternalType( 'string', $actual );
		$this->assertEquals( $expected, $actual );
	}

}
