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
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test\Validators;


use Wikibase\Validators\NumberRangeValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class StringLengthValidatorTest
 * @covers Wikibase\Validators\StringLengthValidator
 * @package Wikibase\Test\Validators
 */
class NumberRangeValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 1, 10, 3, true, "normal fit" ),
			array( 0, 10, 0, true, "0 ok" ),
			array( 1, 10, 0, false, "0 not allowed" ),
			array( -2, 1, -2, true, "negative match" ),
			array( 1, 2, 3, false, "too high" ),
			array( -1, 0, -3, false, "too low" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $minLength, $maxLength, $value, $expected, $message ) {
		$validator = new NumberRangeValidator( $minLength, $maxLength );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertTrue( in_array( $errors[0]->getCode(), array( 'too-low', 'too-high' ) ), $message . "\n" . $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}