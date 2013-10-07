<?php

namespace Wikibase\Test\Validators;

use DataValues\StringValue;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\DataValueValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class DataValueValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		$validator = new StringLengthValidator( 1, 10 );

		return array(
			array( $validator, new StringValue( '' ), false, null, "mismatch" ),
			array( $validator, new StringValue( 'foo' ), true, null, "match" ),
			array( $validator, 'xyz', false, 'InvalidArgumentException', "not a DataValue" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validator, $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$validator = new DataValueValidator( $validator );
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