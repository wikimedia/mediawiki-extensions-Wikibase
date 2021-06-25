<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\StringLengthValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\CompositeValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CompositeValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideValidate() {
		$validators = [
			new TypeValidator( 'string' ),
			new StringLengthValidator( 1, 10 ),
			new RegexValidator( '/xxx/', true ),
		];

		return [
			[ [], true, 'foo', 0, "no validators" ],
			[ $validators, true, 'foo', 0, "pass validation" ],
			[ $validators, true, new StringValue( "foo" ), 1, "fail first validation" ],
			[ $validators, true, '', 1, "fail second validation" ],
			[ $validators, false, str_repeat( 'x', 20 ), 2, "fail second and third validation" ],
			[ $validators, false, str_repeat( 'x', 5 ), 1, "fail third validation" ],
		];
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $validators, $failFast, $value, $expectedErrorCount, $message ) {
		$validator = new CompositeValidator( $validators, $failFast );
		$result = $validator->validate( $value );
		$errors = $result->getErrors();

		$this->assertEquals( $expectedErrorCount === 0, $result->isValid(), $message );
		$this->assertCount( $expectedErrorCount, $errors, $message );

		$localizer = new ValidatorErrorLocalizer();

		foreach ( $errors as $error ) {
			$msg = $localizer->getErrorMessage( $error );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
