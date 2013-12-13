<?php

namespace Wikibase\Test\Validators;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\Validators\NumberValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

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

}
