<?php
namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Validators\NotEntityIdValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Validators\NotEntityIdValidator
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NotEntityIdValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			'empty' => array( '', null, null ),
			'silly' => array( 'silly', null, null ),
			'allowed type' => array( 'Q13', array( Property::ENTITY_TYPE ), null ),
			'forbidden type' => array( 'P13', array( Property::ENTITY_TYPE ), 'label-no-entityid' ),
			'all forbidden' => array( 'Q13', null, 'label-no-entityid' ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $value, $forbiddenTypes, $expectedCode ) {
		$idParser = new BasicEntityIdParser();
		$validator = new NotEntityIdValidator( $idParser, $forbiddenTypes );
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
