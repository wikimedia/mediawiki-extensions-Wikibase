<?php

namespace Wikibase\Test\Validators;

use ValueValidators\Error;
use Wikibase\Validators\NumberValidator;

/**
 * @covers Wikibase\Validators\NumberValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class NumberValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $value, $expected, $message ) {
		$validator = new NumberValidator();
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );
	}

	public function validateProvider() {
		$data = array(
			array( 2, true, 'integer is valid' ),
			array( 3.5, true, 'float is valid' ),
			array( -20, true, 'negative integer is valid' ),
			array( '3.4', false, 'string is invalid' ),
			array( false, false, 'boolean is invalid' ),
			array( null, false, 'null is invalid' )
		);

		return $data;
	}

	/**
	 * @dataProvider validateErrorProvider
	 */
	public function testValidateError( $value, $message ) {
		$validator = new NumberValidator();
		$result = $validator->validate( $value );
		$errors = array( $this->newError( $value ) );

		$this->assertEquals( $errors, $result->getErrors(), $message );
	}

	public function validateErrorProvider() {
		$data = array(
			array( false, 'boolean is invalid' ),
			array( null, 'null is invalid' ),
			array( '4.33', 'string is invalid' )
		);

		return $data;
	}

	private function newError( $value ) {
		return Error::newError(
			'Bad type, expected an integer or float value',
			null,
			'bad-type',
			array( 'integer or float', gettype( $value ) )
		);
	}

}
