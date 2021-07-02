<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\StringLengthValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class StringLengthValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideValidate() {
		return [
			[ 1, 10, 'strlen', 'foo', true, "normal fit" ],
			[ 0, 10, 'strlen', '', true, "empty ok" ],
			[ 1, 10, 'strlen', '', false, "empty not allowed" ],
			[ 1, 2, 'strlen', str_repeat( 'a', 33 ), false, 'too long' ],
			[ 1, 2, 'strlen', 'ää', false, "byte measure" ], // assumes utf-8, latin1 will fail
			[ 1, 2, 'mb_strlen', 'ää', true, "char measure" ],
		];
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
			$this->assertContains(
				$errors[0]->getCode(), [ 'too-long', 'too-short' ],
				$message . "\n" . $errors[0]->getCode()
			);

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

	public function testWhenErrorCodePrefixIsPassedAndErrorIsTriggered() {
		$errorCodePrefix = '[PREFIX]';
		$maxLength = 0;
		$tooLongFunction = function() use ( $maxLength ) {
			return $maxLength + 1;
		};

		$errorCode = ( new StringLengthValidator( 0, $maxLength, $tooLongFunction, $errorCodePrefix ) )
		->validate( null )->getErrors()[0]->getCode();

		$this->assertStringStartsWith( $errorCodePrefix, $errorCode, 'Then error code starts with prefix' );
	}

}
