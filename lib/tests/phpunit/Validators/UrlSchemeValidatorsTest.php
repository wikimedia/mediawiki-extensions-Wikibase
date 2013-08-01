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

use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Validators\UrlSchemeValidators;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * Class UrlSchemeValidatorsTest
 * @covers Wikibase\Validators\UrlSchemeValidators
 * @package Wikibase\Test\Validators
 */
class UrlSchemeValidatorsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validUrlProvider
	 */
	public function testValidUrl( $scheme, $url ) {
		/* @var ValueValidator $validator */
		$factory = new UrlSchemeValidators();
		$validator = $factory->$scheme();
		$result = $validator->validate( $url );

		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @dataProvider invalidUrlProvider
	 */
	public function testInvalidUrl( $scheme, $url ) {
		/* @var ValueValidator $validator */
		$factory = new UrlSchemeValidators();
		$validator = $factory->$scheme();
		$result = $validator->validate( $url );

		$this->assertFalse( $result->isValid() );
		$this->assertErrorCodeLocalization( $result );
	}

	public function validUrlProvider() {
		return array(
			array( 'http', 'http://acme.com' ),
			array( 'http', 'http://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ),
			array( 'https', 'https://acme.com' ),
			array( 'https', 'https://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ),
			array( 'mailto', 'mailto:foo@bar' ),
			array( 'mailto', 'mailto:Eve.Elder+spam@some.place.else?Subject=test' ),
			array( 'any', 'http://acme.com' ),
			array( 'any', 'dummy:some/stuff' ),
			array( 'any', 'dummy+me:other-stuff' ),
			array( 'any', 'dummy-you:some?things' ),
			array( 'any', 'dummy.do:other#things' ),
		);
	}

	public function invalidUrlProvider() {
		return array(
			array( 'http', 'yadda' ),
			array( 'http', 'http:' ),
			array( 'http', 'http://' ),
			array( 'http', 'http://acme.com/foo' . "\n" . 'bar' ),
			array( 'http', '*http://acme.com/foo' ),
			array( 'https', 'yadda' ),
			array( 'https', 'https:' ),
			array( 'https', 'https://' ),
			array( 'https', 'https://acme.com/foo' . "\n" . 'bar' ),
			array( 'https', '*https://acme.com/foo' ),
			array( 'mailto', 'yadda' ),
			array( 'mailto', 'mailto:stuff' ),
			array( 'mailto', 'mailto:james@thingy' . "\n" . '.com' ),
			array( 'mailto', '*mailto:james@thingy' ),
			array( 'any', 'yadda' ),
			array( 'any', 'yadda/yadda' ),
			array( 'any', ':' ),
			array( 'any', 'foo:' ),
			array( 'any', ':bar' ),
			array( 'any', 'doo*da:foo' ),
			array( 'any', 'foo:' . "\n" . '.bar' ),
		);
	}

	protected function assertErrorCodeLocalization( Result $result ) {
		$localizer = new ValidatorErrorLocalizer( );

		$errors = $result->getErrors();
		$this->assertGreaterThanOrEqual( 1, $errors );

		foreach ( $errors as $error ) {
			$msg = $localizer->getErrorMessage( $error );
			$this->assertTrue( $msg->exists(), 'message: ' . $msg );
		}
	}

	public function testGetValidator() {
		$fatory = new UrlSchemeValidators();

		$this->assertNotNull( $fatory->getValidator( 'http' ), 'http' );
		$this->assertNotNull( $fatory->getValidator( 'https' ), 'https' );
		$this->assertNotNull( $fatory->getValidator( 'mailto' ), 'mailto' );

		$this->assertNull( $fatory->getValidator( 'notaprotocol' ), 'notaprotocol' );
	}

	public function testGetValidators() {
		$fatory = new UrlSchemeValidators();

		$schemes = array( 'http', 'https', 'dummy' );
		$validators = $fatory->getValidators( $schemes );

		$this->assertEquals( array( 'http', 'https' ), array_keys( $validators ) );

		foreach ( $validators as $validator ) {
			$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );
		}
	}

}