<?php

namespace Wikibase\Test\Validators;

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
	public function testValidate( $value, $expected, $message, $code = null, $params = array() ) {
		$validator = new NumberValidator();
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$result->isValid() ) {
			$errors = $result->getErrors();
			$this->assertEquals( $code, $errors[0]->getCode(), $message );
			$this->assertEquals( $params, $errors[0]->getParameters(), $message );
		}
	}

	public function validateProvider() {
		$data = array(
			array( 2, true, 'integer is valid' ),
			array( 3.5, true, 'float is valid' ),
			array( -20, true, 'negative integer is valid' ),
			array( '3.4', false, 'string is invalid', 'bad-type', array( 'int|float', 'string' ) ),
			array( false, false, 'boolean is invalid', 'bad-type', array( 'int|float', 'boolean' ) ),
			array( null, false, 'null is invalid', 'bad-type', array( 'int|float', 'NULL' ) )
		);

		return $data;
	}

}
