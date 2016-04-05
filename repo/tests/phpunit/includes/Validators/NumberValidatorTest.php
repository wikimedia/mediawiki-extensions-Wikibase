<?php

namespace Wikibase\Test\Repo\Validators;

use Wikibase\Repo\Validators\NumberValidator;

/**
 * @covers Wikibase\Repo\Validators\NumberValidator
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NumberValidatorTest extends \PHPUnit_Framework_TestCase {

	public function validValueProvider() {
		$data = array(
			'int' => array( 2 ),
			'float' => array( 3.5 ),
			'zero' => array( 0 ),
			'negative' => array( -20.2 ),
		);

		return $data;
	}

	/**
	 * @dataProvider validValueProvider
	 */
	public function testValidateValidValue( $value ) {
		$validator = new NumberValidator();
		$result = $validator->validate( $value );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function invalidValueProvider() {
		$data = array(
			'numeric string' => array( '3.4', 'bad-type', array( 'number', 'string' ) ),
			'boolean' => array( false, 'bad-type', array( 'number', 'boolean' ) ),
			'null' => array( null, 'bad-type', array( 'number', 'NULL' ) )
		);

		return $data;
	}

	/**
	 * @dataProvider invalidValueProvider
	 */
	public function testValidateInvalidValue( $value, $code, array $params = [] ) {
		$validator = new NumberValidator();
		$result = $validator->validate( $value );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertCount( 1, $errors, 'error count' );
		$this->assertEquals( $code, $errors[0]->getCode(), 'error code' );
		$this->assertEquals( $params, $errors[0]->getParameters(), 'error parameters' );
	}

}
