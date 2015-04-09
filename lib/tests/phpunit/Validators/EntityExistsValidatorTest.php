<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityLookup;
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

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$q8 = new Item( new ItemId( 'Q8' ) );

		$p8 = Property::newFromType( 'string' );
		$p8->setId( 8 );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );
		$entityLookup->putEntity( $p8 );

		return $entityLookup;
	}

	public function provideValidate() {
		return array(
			"existing entity" => array( new ItemId( 'Q8' ), null ),
			"is an item" => array( new ItemId( 'Q8' ), Item::ENTITY_TYPE ),
			"is a property" => array( new PropertyId( 'P8' ), Property::ENTITY_TYPE ),
		);
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $value, $type ) {
		$validator = new EntityExistsValidator( $this->getEntityLookup(), $type );
		$result = $validator->validate( $value );

		$this->assertTrue( $result->isValid() );
	}

	public function provideValidate_failure() {
		return array(
			"missing entity" => array( new ItemId( 'Q3' ), null, 'no-such-entity' ),
			"not an item" => array( new PropertyId( 'P8' ), Item::ENTITY_TYPE, 'bad-entity-type' ),
			"not a property" => array( new ItemId( 'Q8' ), Property::ENTITY_TYPE, 'bad-entity-type' ),
		);
	}

	/**
	 * @dataProvider provideValidate_failure()
	 */
	public function testValidate_failure( $value, $type, $errorCode ) {
		$validator = new EntityExistsValidator( $this->getEntityLookup(), $type );
		$result = $validator->validate( $value );

		$this->assertFalse( $result->isValid() );

		$errors = $result->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( $errorCode, $errors[0]->getCode() );

		$localizer = new ValidatorErrorLocalizer( );
		$msg = $localizer->getErrorMessage( $errors[0] );
		$this->assertTrue( $msg->exists(), $msg );
	}

	public function provideValidate_exception() {
		return array(
			"Not an EntityId" => array( 'Q3', null ),
			"Type is not a string" => array( new ItemId( 'Q8' ), array( 'foo' ) ),
		);
	}

	/**
	 * @dataProvider provideValidate_exception()
	 */
	public function testValidate_exception( $value, $type ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$validator = new EntityExistsValidator( $this->getEntityLookup(), $type );
		$validator->validate( $value );
	}

}
