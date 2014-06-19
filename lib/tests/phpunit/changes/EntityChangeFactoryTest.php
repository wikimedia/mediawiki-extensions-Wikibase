<?php

namespace Wikibase\Lib\Test\Change;

use Wikibase\ChangesTable;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityChange;
use Wikibase\EntityFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;

/**
 * @covers Wikibase\Lib\EntityChangeFactory
 *
 * @since 0.5
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityChangeFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		// NOTE: always use a local changes table for testing!
		$changesDatabase = false;

		$entityClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Item',
			Property::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Property',
		);

		$changeClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\ItemChange',
		);

		$factory = new EntityChangeFactory(
			new ChangesTable( $changesDatabase ),
			new EntityFactory( $entityClasses ),
			$changeClasses
		);

		return $factory;
	}

	public function newForEntityProvider() {
		return array(
			'add item' => array( EntityChange::ADD, new ItemId( 'Q17' ), 'Wikibase\ItemChange' ),
			'remove property' => array( EntityChange::REMOVE, new PropertyId( 'P17' ), 'Wikibase\EntityChange' ),
		);
	}

	/**
	 * @dataProvider newForEntityProvider
	 *
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $expectedClass
	 */
	public function testNewForEntity( $action, $entityId, $expectedClass ) {
		$factory = $this->getEntityChangeFactory();

		$change = $factory->newForEntity( $action, $entityId );
		$this->assertInstanceOf( $expectedClass, $change );
		$this->assertEquals( $action, $change->getAction() );
		$this->assertEquals( $entityId, $change->getEntityId() );
	}

	public function newFromUpdateProvider() {
		$item1 = Item::newEmpty();
		$item1->setId( new ItemId( 'Q1' ) );

		$item2 = Item::newEmpty();
		$item2->setId( new ItemId( 'Q2' ) );

		$prop1 = Property::newFromType( 'string' );
		$prop1->setId( new PropertyId( 'P1' ) );

		return array(
			'add item' => array( EntityChange::ADD, null, $item1, 'wikibase-item~add' ),
			'update item' => array( EntityChange::UPDATE, $item1, $item2, 'wikibase-item~update' ),
			'remove property' => array( EntityChange::REMOVE, $prop1, null, 'wikibase-property~remove' ),
		);
	}

	/**
	 * @dataProvider newFromUpdateProvider
	 *
	 * @param string $action
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 * @param string $expectedType
	 */
	public function testNewFromUpdate( $action, $oldEntity, $newEntity, $expectedType ) {
		$factory = $this->getEntityChangeFactory();

		$entityId = ( $newEntity === null ) ? $oldEntity->getId() : $newEntity->getId();

		$change = $factory->newFromUpdate( $action, $oldEntity, $newEntity );

		$this->assertEquals( $action, $change->getAction() );
		$this->assertEquals( $entityId, $change->getEntityId() );
		$this->assertEquals( $expectedType, $change->getType() );
	}

}
