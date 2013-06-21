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

use DataValues\StringValue;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class DataValueValidatorTest
 * @covers Wikibase\Validators\DataValueValidator
 * @package Wikibase\Test\Validators
 */
class DataValueValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return array(
			array( $validator, new StringValue( '' ), false, null, "mismatch" ),
			array( $validator, new StringValue( 'foo' ), true, null, "match" ),
			array( $validator, 'xyz', false, 'InvalidArgumentException', "not a DataValue" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validator, $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$validator = new DataValueValidator( $validator );
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