<?php

namespace Wikibase\Test\Validators;

use Wikibase\Validators\DataFieldValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\DataFieldValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class DataFieldValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return array(
			array( 'a', $validator, array( 'a' => '', 'b' => 'foo' ), false, null, "mismatch assoc" ),
			array( 'a', $validator, array( 'a' => 'foo', 'b' => '' ), true, null, "match assoc" ),
			array( 1, $validator, array( 'x', '', 'foo' ), false, null, "mismatch indexed" ),
			array( 1, $validator, array( 'x', 'foo', '' ), true, null, "match indexed" ),
			array( 17, $validator, array( 'x', 'foo', '' ), false, 'InvalidArgumentException', "bad index" ),
			array( 1, $validator, 'xyz', false, 'InvalidArgumentException', "not an array" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $field, $validator, $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$validator = new DataFieldValidator( $field, $validator );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}