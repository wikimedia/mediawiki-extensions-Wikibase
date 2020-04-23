<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Validators\NotEntityIdValidator;

/**
 * @covers \Wikibase\Repo\Validators\NotEntityIdValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NotEntityIdValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $errorCode ) {
		$this->expectException( InvalidArgumentException::class );
		new NotEntityIdValidator( new BasicEntityIdParser(), $errorCode );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ null ],
			[ 1 ],
		];
	}

	public function provideValidate() {
		return [
			'empty' => [ '', 'label-no-entityid', null, null ],
			'silly' => [ 'silly', 'label-no-entityid', null, null ],
			'allowed type' => [ 'Q13', 'label-no-entityid', [ Property::ENTITY_TYPE ], null ],
			'forbidden type' => [ 'P13', 'label-no-entityid', [ Property::ENTITY_TYPE ], 'label-no-entityid' ],
			'all forbidden' => [ 'Q13', 'label-no-entityid', null, 'label-no-entityid' ],
		];
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate( $value, $code, ?array $forbiddenTypes, $expectedCode ) {
		$idParser = new BasicEntityIdParser();
		$validator = new NotEntityIdValidator( $idParser, $code, $forbiddenTypes );
		$result = $validator->validate( $value );

		if ( $expectedCode === null ) {
			$this->assertTrue( $result->isValid(), 'isValid()' );
		} else {
			$this->assertFalse( $result->isValid(), 'isValid()' );

			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, 'Number of errors:' );
			$this->assertEquals( $expectedCode, $errors[0]->getCode(), 'Error code:' );
		}
	}

}
