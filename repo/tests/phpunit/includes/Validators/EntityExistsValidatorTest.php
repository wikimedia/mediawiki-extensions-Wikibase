<?php

namespace Wikibase\Test\Repo\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\Validators\EntityExistsValidator
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
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
		$itemId = new ItemId( 'Q8' );

		return array(
			'existing entity' => array( new EntityIdValue( $itemId ), null ),
			'is an item' => array( $itemId, Item::ENTITY_TYPE ),
			'is a property' => array( new PropertyId( 'P8' ), Property::ENTITY_TYPE ),
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

		$localizer = new ValidatorErrorLocalizer();
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
		$this->setExpectedException( InvalidArgumentException::class );

		$validator = new EntityExistsValidator( $this->getEntityLookup(), $type );
		$validator->validate( $value );
	}

}
