<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Repo\Validators\NumberRangeValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\NumberRangeValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NumberRangeValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideValidate() {
		return [
			[ 1, 10, 3, true, "normal fit" ],
			[ 0, 10, 0, true, "0 ok" ],
			[ 1, 10, 0, false, "0 not allowed" ],
			[ -2, 1, -2, true, "negative match" ],
			[ 1, 2, 3, false, "too high" ],
			[ -1, 0, -3, false, "too low" ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $minLength, $maxLength, $value, $expected, $message ) {
		$validator = new NumberRangeValidator( $minLength, $maxLength );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertContains(
				$errors[0]->getCode(), [ 'too-low', 'too-high' ],
				$message . "\n" . $errors[0]->getCode()
			);

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
