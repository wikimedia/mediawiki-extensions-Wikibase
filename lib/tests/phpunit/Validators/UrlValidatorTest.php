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

use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\UrlValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class UrlValidatorTest
 * @covers Wikibase\Validators\UrlValidator
 * @package Wikibase\Test\Validators
 */
class UrlValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$yes = new RegexValidator( '/.*/', false, 'oops' );
		$no  = new RegexValidator( '/.*/', true, 'bad-http-url' );

		return array(
			'empty' => array( array(), 'http://acme.com', 'bad-url-scheme' ),
			'valid' => array( array( 'http' => $yes ), 'http://acme.com', null ),
			'invalid' => array( array( 'http' => $no ), 'http://acme.com', 'bad-http-url' ),
			'wildcard' => array( array( '*' => $yes ), 'http://acme.com', null ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $schemes, $value, $expectedErrorCode ) {
		$validator = new UrlValidator( $schemes );
		$result = $validator->validate( $value );

		if ( $expectedErrorCode === null ) {
			$this->assertTrue( $result->isValid(), 'isValid' );
		} else {
			$this->assertFalse( $result->isValid(), 'isValid' );

			$errors = $result->getErrors();
			$this->assertCount( 1, $errors );
			$this->assertEquals( $expectedErrorCode, $errors[0]->getCode(), 'error code' );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), 'message: ' . $msg );
		}
	}

}