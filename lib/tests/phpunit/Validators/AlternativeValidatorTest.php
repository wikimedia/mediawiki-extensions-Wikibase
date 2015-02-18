<?php

namespace Wikibase\Test\Validators;

use Wikibase\Validators\AlternativeValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\AlternativeValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class AlternativeValidatorTest extends \PHPUnit_Framework_TestCase {

	public function provideValidate() {
		$validators = array(
			new RegexValidator( '/aaa/' ),
			new RegexValidator( '/bbb/' ),
			new RegexValidator( '/ccc/' ),
		);

		return array(
			array( array(), 'foo', 1, "no validators" ),
			array( $validators, 'bbb', 0, "fail first validation" ),
			array( $validators, 'aaa', 0, "fail second validation" ),
			array( $validators, 'xxx', 3, "fail validations" ),
		);
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

		$localizer = new ValidatorErrorLocalizer( );

		foreach ( $errors as $error ) {
			$msg = $localizer->getErrorMessage( $error );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}