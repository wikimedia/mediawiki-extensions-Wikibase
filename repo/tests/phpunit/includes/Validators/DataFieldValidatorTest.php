<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use Wikibase\Repo\Validators\DataFieldValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\DataFieldValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataFieldValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $field ) {
		$this->expectException( InvalidArgumentException::class );
		new DataFieldValidator( $field, new StringLengthValidator( 0, 0 ) );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ null ],
			[ 1.0 ],
			[ [] ],
		];
	}

	public function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return [
			[ 'a', $validator, [ 'a' => '', 'b' => 'foo' ], 'too-short', null, "mismatch assoc" ],
			[ 'a', $validator, [ 'a' => 'foo', 'b' => '' ], null, null, "match assoc" ],
			[ 1, $validator, [ 'x', '', 'foo' ], 'too-short', null, "mismatch indexed" ],
			[ 1, $validator, [ 'x', 'foo', '' ], null, null, "match indexed" ],
			[ 'a', $validator, [], 'missing-field', null, "missing field" ],
			[ 'a', $validator, [ 'a' => null ], 'missing-field', null, "field is null" ],
			[ 1, $validator, 'xyz', null, InvalidArgumentException::class, 'not an array' ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $field, $validator, $value, $expectedError, $expectedException, $message ) {
		if ( $expectedException !== null ) {
			$this->expectException( $expectedException );
		}

		$validator = new DataFieldValidator( $field, $validator );
		$result = $validator->validate( $value );

		$this->assertEquals( $expectedError === null, $result->isValid(), $message );

		if ( $expectedError !== null ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );

			$this->assertEquals( $expectedError, $errors[0]->getCode(), $message );

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
