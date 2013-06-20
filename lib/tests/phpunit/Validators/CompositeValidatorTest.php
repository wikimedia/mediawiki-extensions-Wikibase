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


use DataValues\StringValue;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\TypeValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class CompositeValidatorTest
 * @covers Wikibase\Validators\CompositeValidator
 * @package Wikibase\Test\Validators
 */
class CompositeValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$validators = array(
			new TypeValidator( 'string' ),
			new StringLengthValidator( 1, 10 ),
			new RegexValidator( '/xxx/', true ),
		);

		return array(
			array( array(), true, 'foo', 0, "no validators" ),
			array( $validators, true, 'foo', 0, "pass validation" ),
			array( $validators, true, new StringValue( "foo" ), 1, "fail first validation" ),
			array( $validators, true, '', 1, "fail second validation" ),
			array( $validators, false, str_repeat( 'x', 20 ), 2, "fail second and third validation" ),
			array( $validators, false, str_repeat( 'x', 5 ), 1, "fail third validation" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validators, $failFast, $value, $expectedErrorCount, $message ) {
		$validator = new CompositeValidator( $validators, $failFast );
		$result = $validator->validate( $value );
		$errors = $result->getErrors();

		$this->assertEquals( $expectedErrorCount === 0, $result->isValid(), $message );
		$this->assertCount( $expectedErrorCount, $errors, $message );

		$localizer = new ValidatorErrorLocalizer( );

		foreach ( $errors as $error ) {
			$msg = $localizer->getErrorMessage( $error );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}