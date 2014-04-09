<?php
namespace Wikibase\Test\Validators;

use Wikibase\Validators\MembershipValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Validators\MembershipValidator
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MembershipValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
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

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
