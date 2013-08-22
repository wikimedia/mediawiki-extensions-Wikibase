<?php

namespace Wikibase\Test\Validators;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\Validators\TypeValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @license GPL 2+
 * @file
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */

/**
 * Class TypeValidatorTest
 * @covers Wikibase\Validators\TypeValidator
 * @package Wikibase\Test\Validators
 */
class TypeValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 'integer', 1, true, "integer" ),
			array( 'integer', 1.1, false, "not an integer" ),
			array( 'object', new StringValue( "foo" ), true, "object" ),
			array( 'object', "foo", false, "not an object" ),
			array( 'DataValues\StringValue', new StringValue( "foo" ), true, "StringValue" ),
			array( 'DataValues\StringValue', new NumberValue( 7 ), false, "not a StringValue" ),
			array( 'DataValues\StringValue', 33, false, "definitly not a StringValue" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $type, $value, $expected, $message ) {
		$validator = new TypeValidator( $type );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertEquals( 'bad-type', $errors[0]->getCode(), $message );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}