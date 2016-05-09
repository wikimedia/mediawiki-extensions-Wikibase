<?php

namespace Wikibase\Lib\Test\Change;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Lib\Changes\EntityChangeFactory;

/**
 * @covers Wikibase\Lib\Changes\EntityChangeFactory
 *
 * @since 0.5
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityChangeFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		$changeClasses = array(
			Item::ENTITY_TYPE => ItemChange::class,
		);

		$factory = new EntityChangeFactory(
			new EntityDiffer(),
			$changeClasses
		);

		return $factory;
	}

	public function newForEntityProvider() {
		return array(
			'add item' => array( EntityChange::ADD, new ItemId( 'Q17' ), ItemChange::class ),
			'remove property' => array( EntityChange::REMOVE, new PropertyId( 'P17' ), EntityChange::class ),
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

	/**
	 * @dataProvider newForEntityProvider
	 *
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $expectedClass
	 */
	public function testNewForChangeType( $action, $entityId, $expectedClass ) {
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$factory = $this->getEntityChangeFactory();

		$change = $factory->newForChangeType( $type, $entityId, [] );
		$this->assertInstanceOf( $expectedClass, $change );
		$this->assertEquals( $type, $change->getType() );
		$this->assertEquals( $action, $change->getAction() );
		$this->assertEquals( $entityId, $change->getEntityId() );
	}

	public function testNewFromUpdate() {
		$itemId = new ItemId( 'Q1' );

		$item = new Item( $itemId );
		$item->setLabel( 'en', 'kitten' );

		$updatedItem = new Item( $itemId );
		$updatedItem->setLabel( 'en', 'kitten' );
		$updatedItem->setLabel( 'es', 'gato' );

		$factory = $this->getEntityChangeFactory();

		$change = $factory->newFromUpdate( EntityChange::UPDATE, $item, $updatedItem );

		$this->assertEquals( $itemId, $change->getEntityId(), 'entity id' );
		$this->assertEquals( 'q1', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~update', $change->getType(), 'type' );

		$this->assertEquals(
			new Diff( array( 'es' => new DiffOpAdd( 'gato' ) ) ),
			$change->getDiff()->getLabelsDiff(),
			'diff'
		);
	}

	public function testNewFromUpdate_add() {
		$itemId = new ItemId( 'Q1' );

		$item = new Item( $itemId );
		$item->setLabel( 'en', 'kitten' );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->newFromUpdate( EntityChange::ADD, null, $item );

		$this->assertEquals( $itemId, $change->getEntityId(), 'entity id' );
		$this->assertEquals( 'q1', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~add', $change->getType(), 'type' );

		$this->assertEquals(
			new Diff( array( 'en' => new DiffOpAdd( 'kitten' ) ) ),
			$change->getDiff()->getLabelsDiff(),
			'diff'
		);
	}

	public function testNewFromUpdate_remove() {
		$propertyId = new PropertyId( 'P2' );

		$property = new Property( $propertyId, null, 'string' );
		$property->setLabel( 'de', 'Katze' );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->newFromUpdate( EntityChange::REMOVE, $property, null );

		$this->assertEquals( $propertyId, $change->getEntityId(), 'entity id' );
		$this->assertEquals( 'p2', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-property~remove', $change->getType(), 'type' );

		$this->assertEquals(
			new Diff( array( 'de' => new DiffOpRemove( 'Katze' ) ) ),
			$change->getDiff()->getLabelsDiff(),
			'diff'
		);
	}

	public function testNewFromUpdate_restore() {
		$itemId = new ItemId( 'Q4' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->newFromUpdate( EntityChange::RESTORE, null, $item );

		$this->assertEquals( $itemId, $change->getEntityId(), 'entity id' );
		$this->assertEquals( 'q4', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~restore', $change->getType(), 'type' );

		$this->assertEquals(
			new Diff( array(
				'enwiki' => new Diff( array(
					'name' => new DiffOpAdd( 'Kitten' )
				) )
			) ),
			$change->getDiff()->getSiteLinkDiff(),
			'diff'
		);
	}

	public function testNewFromUpdate_excludeStatementsInDiffs() {
		$factory = $this->getEntityChangeFactory();

		$item = new Item( new ItemId( 'Q3' ) );
		$statementList = new StatementList( array(
			new Statement( new PropertyNoValueSnak( 9000 ) )
		) );

		$item->setStatements( $statementList );

		$updatedItem = new Item( new ItemId( 'Q3' ) );
		$statementList = new StatementList( array(
			new Statement( new PropertyNoValueSnak( 10 ) )
		) );

		$updatedItem->setStatements( $statementList );

		$change = $factory->newFromUpdate( EntityChange::UPDATE, $item, $updatedItem );

		$this->assertTrue(
			$change->getDiff()->isEmpty(),
			'Diff excludes statement changes and is empty'
		);
	}

}
