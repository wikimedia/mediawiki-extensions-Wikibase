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

use Wikibase\Validators\DataFieldValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class DataFieldValidatorTest
 * @covers Wikibase\Validators\DataFieldValidator
 * @package Wikibase\Test\Validators
 */
class DataFieldValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return array(
			array( 'a', $validator, array( 'a' => '', 'b' => 'foo' ), false, null, "mismatch assoc" ),
			array( 'a', $validator, array( 'a' => 'foo', 'b' => '' ), true, null, "match assoc" ),
			array( 1, $validator, array( 'x', '', 'foo' ), false, null, "mismatch indexed" ),
			array( 1, $validator, array( 'x', 'foo', '' ), true, null, "match indexed" ),
			array( 17, $validator, array( 'x', 'foo', '' ), false, 'InvalidArgumentException', "bad index" ),
			array( 1, $validator, 'xyz', false, 'InvalidArgumentException', "not an array" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $field, $validator, $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$validator = new DataFieldValidator( $field, $validator );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}