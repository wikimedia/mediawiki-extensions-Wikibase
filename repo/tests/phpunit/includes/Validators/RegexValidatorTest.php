<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\RegexValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RegexValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideValidate() {
		return [
			[ '/^x/', false, 'xyz', true, "match" ],
			[ '/^x/', false, 'zyx', false, "mismatch" ],
			[ '/^x/', true, 'zyx', true, "inverse match" ],
			[ '/^x/', true, 'xyz', false, "inverse mismatch" ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $regex, $inverse, $value, $expected, $message ) {
		$validator = new RegexValidator( $regex, $inverse );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertEquals( 'malformed-value', $errors[0]->getCode(), $message );

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
