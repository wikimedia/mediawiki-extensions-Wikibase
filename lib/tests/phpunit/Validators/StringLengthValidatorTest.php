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


use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class StringLengthValidatorTest
 * @covers Wikibase\Validators\StringLengthValidator
 * @package Wikibase\Test\Validators
 */
class StringLengthValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 1, 10, 'strlen', 'foo', true, "normal fit" ),
			array( 0, 10, 'strlen', '', true, "empty ok" ),
			array( 1, 10, 'strlen', '', false, "empty not allowed" ),
			array( 1, 2, 'strlen', 'foo', false, "too long" ),
			array( 1, 2, 'strlen', 'ää', false, "byte measure" ), // assumes utf-8, latin1 will fail
			array( 1, 2, 'mb_strlen', 'ää', true, "char measure" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $minLength, $maxLength, $measure, $value, $expected, $message ) {
		$validator = new StringLengthValidator( $minLength, $maxLength, $measure );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertTrue( in_array( $errors[0]->getCode(), array( 'too-long', 'too-short' ) ), $message . "\n" . $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}