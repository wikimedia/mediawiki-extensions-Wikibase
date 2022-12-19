<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\Repo\Validators\NumberValidator;

/**
 * @covers \Wikibase\Repo\Validators\NumberValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NumberValidatorTest extends \PHPUnit\Framework\TestCase {

	public function validValueProvider() {
		$data = [
			'int' => [ 2 ],
			'float' => [ 3.5 ],
			'zero' => [ 0 ],
			'negative' => [ -20.2 ],
		];

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
		$data = [
			'numeric string' => [ '3.4', 'bad-type', [ 'number', 'string' ] ],
			'boolean' => [ false, 'bad-type', [ 'number', 'boolean' ] ],
			'null' => [ null, 'bad-type', [ 'number', 'NULL' ] ],
		];

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
