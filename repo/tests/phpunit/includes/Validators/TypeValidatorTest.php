<?php

namespace Wikibase\Test\Repo\Validators;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Repo\Validators\TypeValidator
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TypeValidatorTest extends \PHPUnit_Framework_TestCase {

	public function provideValidate() {
		return array(
			array( 'integer', 1, true, "integer" ),
			array( 'integer', 1.1, false, "not an integer" ),
			array( 'object', new StringValue( "foo" ), true, "object" ),
			array( 'object', "foo", false, "not an object" ),
			array( StringValue::class, new StringValue( "foo" ), true, "StringValue" ),
			array( StringValue::class, new NumberValue( 7 ), false, "not a StringValue" ),
			array( StringValue::class, 33, false, "definitly not a StringValue" ),
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

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
