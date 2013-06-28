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


use DataValues\NumberValue;
use DataValues\StringValue;
use ValueValidators\Error;
use Wikibase\Validators\TypeValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class TypeValidatorTest
 * @covers Wikibase\Validators\ValidatorErrorLocalizer
 * @package Wikibase\Test\Validators
 */
class ValidatorErrorLocalizerTest extends \PHPUnit_Framework_TestCase {

	public static function provideGetErrorMessage() {
		return array(
			array( Error::newError( 'Bla bla' ) ),
			array( Error::newError( 'Bla bla', null, 'test', array( 'thingy' ) ) ),
		);
	}

	/**
	 * @dataProvider provideGetErrorMessage()
	 */
	public function testGetErrorMessage( $error ) {
		$localizer = new ValidatorErrorLocalizer( );
		$message = $localizer->getErrorMessage( $error );

		$this->assertInstanceOf( 'Message', $message );
	}

}