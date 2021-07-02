<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Repo\Validators\AlternativeValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\AlternativeValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class AlternativeValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideValidate() {
		$validators = [
			new RegexValidator( '/aaa/' ),
			new RegexValidator( '/bbb/' ),
			new RegexValidator( '/ccc/' ),
		];

		return [
			[ [], 'foo', 1, "no validators" ],
			[ $validators, 'bbb', 0, "fail first validation" ],
			[ $validators, 'aaa', 0, "fail second validation" ],
			[ $validators, 'xxx', 3, "fail validations" ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validators, $value, $expectedErrorCount, $message ) {
		$validator = new AlternativeValidator( $validators );
		$result = $validator->validate( $value );
		$errors = $result->getErrors();

		$this->assertEquals( $expectedErrorCount === 0, $result->isValid(), $message );
		$this->assertCount( $expectedErrorCount, $errors, $message );

		$localizer = new ValidatorErrorLocalizer();

		foreach ( $errors as $error ) {
			$msg = $localizer->getErrorMessage( $error );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
