<?php

namespace Wikibase\Test\Validators;

use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Validators\UrlSchemeValidators;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\UrlSchemeValidators
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
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
			array( 'ftp', 'ftp://acme.com' ),
			array( 'ftp', 'ftp://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ),
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
			array( 'ftp', 'yadda' ),
			array( 'ftp', 'ftp:' ),
			array( 'ftp', 'ftp://' ),
			array( 'ftp', 'ftp://acme.com/foo' . "\n" . 'bar' ),
			array( 'ftp', '*ftp://acme.com/foo' ),
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
		$factory = new UrlSchemeValidators();

		$this->assertNotNull( $factory->getValidator( 'http' ), 'http' );
		$this->assertNotNull( $factory->getValidator( 'https' ), 'https' );
		$this->assertNotNull( $factory->getValidator( 'ftp' ), 'ftp' );
		$this->assertNotNull( $factory->getValidator( 'mailto' ), 'mailto' );

		$this->assertNull( $factory->getValidator( 'notaprotocol' ), 'notaprotocol' );
	}

	public function testGetValidators() {
		$factory = new UrlSchemeValidators();

		$schemes = array( 'http', 'https', 'ftp', 'dummy' );
		$validators = $factory->getValidators( $schemes );

		$this->assertEquals( array( 'http', 'https', 'ftp' ), array_keys( $validators ) );
		$this->assertContainsOnlyInstancesOf( 'ValueValidators\ValueValidator', $validators );
	}

}
