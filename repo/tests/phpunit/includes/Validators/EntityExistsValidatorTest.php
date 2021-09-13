<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Validators\EntityExistsValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\EntityExistsValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityExistsValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$q8 = new Item( new ItemId( 'Q8' ) );

		$p8 = new Property( new NumericPropertyId( 'P8' ), null, 'string' );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );
		$entityLookup->putEntity( $p8 );

		return $entityLookup;
	}

	public function provideValidate() {
		$itemId = new ItemId( 'Q8' );

		return [
			'existing entity' => [ new EntityIdValue( $itemId ), null ],
			'is an item' => [ $itemId, Item::ENTITY_TYPE ],
			'is a property' => [ new NumericPropertyId( 'P8' ), Property::ENTITY_TYPE ],
		];
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
		return [
			"missing entity" => [ new ItemId( 'Q3' ), null, 'no-such-entity' ],
			"not an item" => [ new NumericPropertyId( 'P8' ), Item::ENTITY_TYPE, 'bad-entity-type' ],
			"not a property" => [ new ItemId( 'Q8' ), Property::ENTITY_TYPE, 'bad-entity-type' ],
		];
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
		$this->assertTrue( $msg->exists(), $msg->getKey() );
	}

	public function provideValidate_exception() {
		return [
			"Not an EntityId" => [ 'Q3', null ],
			"Type is not a string" => [ new ItemId( 'Q8' ), [ 'foo' ] ],
		];
	}

	/**
	 * @dataProvider provideValidate_exception()
	 */
	public function testValidate_exception( $value, $type ) {
		$this->expectException( InvalidArgumentException::class );

		$validator = new EntityExistsValidator( $this->getEntityLookup(), $type );
		$validator->validate( $value );
	}

}
