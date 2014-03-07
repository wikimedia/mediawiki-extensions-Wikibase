<?php
namespace Wikibase\Test\Validators;

use Wikibase\Validators\NumberRangeValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Validators\NumberRangeValidator
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NumberRangeValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 1, 10, 3, true, "normal fit" ),
			array( 0, 10, 0, true, "0 ok" ),
			array( 1, 10, 0, false, "0 not allowed" ),
			array( -2, 1, -2, true, "negative match" ),
			array( 1, 2, 3, false, "too high" ),
			array( -1, 0, -3, false, "too low" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $minLength, $maxLength, $value, $expected, $message ) {
		$validator = new NumberRangeValidator( $minLength, $maxLength );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertTrue( in_array( $errors[0]->getCode(), array( 'too-low', 'too-high' ) ), $message . "\n" . $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
