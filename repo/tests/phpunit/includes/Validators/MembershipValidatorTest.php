<?php

namespace Wikibase\Test\Repo\Validators;

use InvalidArgumentException;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Repo\Validators\MembershipValidator
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MembershipValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testInvalidConstructorArgument( $errorCode, $normalizer ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new MembershipValidator( [], $errorCode, $normalizer );
	}

	public function invalidConstructorArgumentsProvider() {
		return array(
			array( null, null ),
			array( 1, null ),
			array( '', true ),
			array( '', '' ),
		);
	}

	public function provideValidate() {
		return array(
			'contained' => array( array( 'apple', 'pear' ), null, 'apple', true ),
			'not contained' => array( array( 'apple', 'pear' ), null, 'nuts', false ),
			'case sensitive' => array( array( 'apple', 'pear' ), null, 'Apple', false ),
			'case insitive' => array( array( 'apple', 'pear' ), 'strtolower', 'Apple', true ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $values, $normalize, $value, $expected ) {
		$validator = new MembershipValidator( $values, 'not-allowed', $normalize );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid() );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors );
			$this->assertTrue( in_array( $errors[0]->getCode(), array( 'not-allowed' ) ), $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
