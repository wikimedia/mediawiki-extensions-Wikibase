<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\MembershipValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MembershipValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testInvalidConstructorArgument( $errorCode, $normalizer ) {
		$this->expectException( InvalidArgumentException::class );
		new MembershipValidator( [], $errorCode, $normalizer );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ null, null ],
			[ 1, null ],
			[ '', true ],
			[ '', '' ],
		];
	}

	public function provideValidate() {
		return [
			'contained' => [ [ 'apple', 'pear' ], null, 'apple', true ],
			'not contained' => [ [ 'apple', 'pear' ], null, 'nuts', false ],
			'case sensitive' => [ [ 'apple', 'pear' ], null, 'Apple', false ],
			'case insitive' => [ [ 'apple', 'pear' ], 'strtolower', 'Apple', true ],
		];
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
			$this->assertContains( $errors[0]->getCode(), [ 'not-allowed' ], $errors[0]->getCode() );

			$localizer = new ValidatorErrorLocalizer();
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg->getKey() );
		}
	}

}
