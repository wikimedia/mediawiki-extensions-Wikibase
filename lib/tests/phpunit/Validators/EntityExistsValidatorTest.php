<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Test\MockRepository;
use Wikibase\Validators\EntityExistsValidator;
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
 * Class EntityExistsValidatorTest
 * @covers Wikibase\Validators\EntityExistsValidator
 * @package Wikibase\Test\Validators
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

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );

		$validator = new EntityExistsValidator( $entityLookup );
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