<?php

namespace Wikibase\Repo\Tests\Validators;

use MediaWikiIntegrationTestCase;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Repo\Validators\UrlSchemeValidators;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\UrlSchemeValidators
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class UrlSchemeValidatorsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider validUrlProvider
	 */
	public function testValidUrl( $scheme, $url ) {
		$factory = new UrlSchemeValidators();
		$validator = $factory->getValidator( $scheme );
		$result = $validator->validate( $url );

		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @dataProvider invalidUrlProvider
	 */
	public function testInvalidUrl( $scheme, $url ) {
		$factory = new UrlSchemeValidators();
		$validator = $factory->getValidator( $scheme );
		$result = $validator->validate( $url );

		$this->assertFalse( $result->isValid() );
		$this->assertErrorCodeLocalization( $result );
	}

	public function validUrlProvider() {
		return [
			[ 'http', 'http://acme.com' ],
			[ 'http', 'http://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ],
			[ 'https', 'https://acme.com' ],
			[ 'https', 'https://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ],
			[ 'https', 'https://ko.wikipedia.org/wiki/전_(요리)' ],
			[ 'ftp', 'ftp://acme.com' ],
			[ 'ftp', 'ftp://foo:bar@acme.com/stuff/thingy.php?foo=bar#part' ],
			[ 'irc', 'irc://irc.libera.chat/vim' ],
			[ 'bzr', 'bzr://archonproject.bzr.sourceforge.net/bzrroot/archonproject' ],
			[ 'cvs', 'cvs://pserver:anonymous@cvs.delorie.com/cvs/djgpp' ],
			[ 'mailto', 'mailto:foo@bar' ],
			[ 'mailto', 'mailto:random.korean.character.전@bar' ],
			[ 'mailto', 'mailto:Eve.Elder+spam@some.place.else?Subject=test' ],
			[ 'telnet', 'telnet://user:password@host:9999/' ],
			[ 'any', 'http://acme.com' ],
			[ 'any', 'dummy:some/stuff' ],
			[ 'any', 'dummy+me:other-stuff' ],
			[ 'any', 'dummy-you:some?things' ],
			[ 'any', 'dummy.do:other#things' ],
			[ 'any', 'random.korean.character:전' ],
		];
	}

	public function invalidUrlProvider() {
		return [
			// Trailing newlines
			[ 'http', "http://example.com\n" ],
			[ 'mailto', "mailto:mail@example.com\n" ],
			[ 'any', "http://example.com\n" ],

			[ 'http', 'yadda' ],
			[ 'http', 'http:' ],
			[ 'http', 'http://' ],
			[ 'http', 'http://acme.com/foo' . "\n" . 'bar' ],
			[ 'http', '*http://acme.com/foo' ],
			[ 'https', 'yadda' ],
			[ 'https', 'https:' ],
			[ 'https', 'https://' ],
			[ 'https', 'https://acme.com/foo' . "\n" . 'bar' ],
			[ 'https', '*https://acme.com/foo' ],
			[ 'ftp', 'yadda' ],
			[ 'ftp', 'ftp:' ],
			[ 'ftp', 'ftp://' ],
			[ 'ftp', 'ftp://acme.com/foo' . "\n" . 'bar' ],
			[ 'ftp', '*ftp://acme.com/foo' ],
			[ 'cvs', ':pserver:anonymous@cvs.delorie.com:/cvs/djgpp' ],
			[ 'mailto', 'yadda' ],
			[ 'mailto', 'mailto:stuff' ],
			[ 'mailto', 'mailto:james@thingy' . "\n" . '.com' ],
			[ 'mailto', '*mailto:james@thingy' ],
			[ 'any', 'yadda' ],
			[ 'any', 'yadda/yadda' ],
			[ 'any', ':' ],
			[ 'any', 'foo:' ],
			[ 'any', ':bar' ],
			[ 'any', '+must:start-with-character' ],
			[ 'any', '.must:start-with-character' ],
			[ 'any', '-must:start-with-character' ],
			[ 'any', '0must:start-with-character' ],
			[ 'any', 'doo*da:foo' ],
			[ 'any', 'foo:' . "\n" . '.bar' ],
		];
	}

	protected function assertErrorCodeLocalization( Result $result ) {
		$localizer = new ValidatorErrorLocalizer();

		$errors = $result->getErrors();
		$this->assertCount( 1, $errors );

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
		$this->assertNotNull( $factory->getValidator( 'irc' ), 'irc' );
		$this->assertNotNull( $factory->getValidator( 'mailto' ), 'mailto' );
		$this->assertNotNull( $factory->getValidator( 'telnet' ), 'telnet' );

		$this->assertNull( $factory->getValidator( 'notaprotocol' ), 'notaprotocol' );
	}

	public function testGetValidators() {
		$factory = new UrlSchemeValidators();

		$schemes = [ 'http', 'https', 'ftp', 'dummy' ];
		$validators = $factory->getValidators( $schemes );

		$this->assertEquals( [ 'http', 'https', 'ftp' ], array_keys( $validators ) );
		$this->assertContainsOnlyInstancesOf( ValueValidator::class, $validators );
	}

}
