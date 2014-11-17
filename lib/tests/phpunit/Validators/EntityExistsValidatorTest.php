<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Test\MockRepository;
use Wikibase\Validators\EntityExistsValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\EntityExistsValidator
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class EntityExistsValidatorTest extends \PHPUnit_Framework_TestCase {

	public static function provideValidate() {
		return array(
			array( 'q3', false, 'InvalidArgumentException', "Expect an EntityId" ),
			array( new ItemId( 'q3' ), false, null, "missing entity" ),
			array( new ItemId( 'q8' ), true, null, "existing entity" ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $value, $expected, $exception, $message ) {
		if ( $exception !== null ) {
			$this->setExpectedException( $exception );
		}

		$q8 = Item::newEmpty();
		$q8->setId( 8 );

		$mockRepository = new MockRepository();
		$mockRepository->putEntity( $q8 );

		$validator = new EntityExistsValidator( $mockRepository );
		$result = $validator->validate( $value );

		$this->assertEquals( $expected, $result->isValid(), $message );

		if ( !$expected ) {
			$errors = $result->getErrors();
			$this->assertCount( 1, $errors, $message );
			$this->assertEquals( 'no-such-entity', $errors[0]->getCode(), $message );

			$localizer = new ValidatorErrorLocalizer( );
			$msg = $localizer->getErrorMessage( $errors[0] );
			$this->assertTrue( $msg->exists(), $msg );
		}
	}

}
