<?php

namespace Wikibase\Test\Repo\Validators;

use DataValues\StringValue;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Repo\Validators\DataValueValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class DataValueValidatorTest extends \PHPUnit_Framework_TestCase {

	public function provideValidate() {
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

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

	public function testSetOptions() {
		$validator = new DataValueValidator( new StringLengthValidator( 0, 0 ) );
		$validator->setOptions( array() );
	}

}
