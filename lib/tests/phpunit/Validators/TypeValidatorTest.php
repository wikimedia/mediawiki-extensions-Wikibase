<?php
 /**
 *
 * Copyright © 14.06.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test\Validators;


use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\Validators\TypeValidator;

/**
 * Class TypeValidatorTest
 * @covers Wikibase\Validators\TypeValidator
 * @package Wikibase\Test\Validators
 */
class TypeValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 'integer', 1, true, "integer" ),
			array( 'integer', 1.1, false, "not an integer" ),
			array( 'object', new StringValue( "foo" ), true, "object" ),
			array( 'object', "foo", false, "not an object" ),
			array( 'DataValues\StringValue', new StringValue( "foo" ), true, "StringValue" ),
			array( 'DataValues\StringValue', new NumberValue( 7 ), false, "not a StringValue" ),
			array( 'DataValues\StringValue', 33, false, "definitly not a StringValue" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $type, $value, $expected, $message ) {
		$validator = new TypeValidator( $type );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );
	}

}