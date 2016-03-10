<?php

namespace Wikibase\Test\Repo\Validators;

use InvalidArgumentException;
use Wikibase\Repo\Validators\DataFieldValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Repo\Validators\DataFieldValidator
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DataFieldValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $field ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new DataFieldValidator( $field, new StringLengthValidator( 0, 0 ) );
	}

	public function invalidConstructorArgumentProvider() {
		return array(
			array( null ),
			array( 1.0 ),
			array( array() ),
		);
	}

	public function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return array(
			array( 'a', $validator, array( 'a' => '', 'b' => 'foo' ), 'too-short', null, "mismatch assoc" ),
			array( 'a', $validator, array( 'a' => 'foo', 'b' => '' ), null, null, "match assoc" ),
			array( 1, $validator, array( 'x', '', 'foo' ), 'too-short', null, "mismatch indexed" ),
			array( 1, $validator, array( 'x', 'foo', '' ), null, null, "match indexed" ),
			array( 'a', $validator, array(), 'missing-field', null, "missing field" ),
			array( 'a', $validator, array( 'a' => null ), 'missing-field', null, "field is null" ),
			array( 1, $validator, 'xyz', null, 'InvalidArgumentException', "not an array" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $field, $validator, $value, $expectedError, $expectedException, $message ) {
		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
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
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
