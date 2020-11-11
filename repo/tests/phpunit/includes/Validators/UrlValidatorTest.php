<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\UrlValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\UrlValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UrlValidatorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( array $validators ) {
		$this->expectException( InvalidArgumentException::class );
		new UrlValidator( $validators );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ new RegexValidator( '/.*/' ) ] ],
			[ [ 'scheme' => '/.*/' ] ],
		];
	}

	public function testGivenNonString_validateFails() {
		$validator = new UrlValidator( [] );
		$this->expectException( InvalidArgumentException::class );
		$validator->validate( null );
	}

	public function provideValidate() {
		$yes = new RegexValidator( '/.*/', false, 'oops' );
		$no  = new RegexValidator( '/.*/', true, 'bad-url' );

		return [
			'no scheme' => [ [], '', 'url-scheme-missing' ],
			'empty' => [ [], 'http://acme.com', 'bad-url-scheme' ],
			'valid' => [ [ 'http' => $yes ], 'http://acme.com', null ],
			'invalid' => [ [ 'http' => $no ], 'http://acme.com', 'bad-url' ],
			'wildcard' => [ [ '*' => $yes ], 'http://acme.com', null ],
		];
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

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), 'message: ' . $msg );
		}
	}

}
