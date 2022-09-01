<?php

namespace Wikibase\Lib\Tests\Changes;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\ItemChange;

/**
 * @covers \Wikibase\Lib\Changes\EntityChangeFactory
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityChangeFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		$changeClasses = [
			Item::ENTITY_TYPE => ItemChange::class,
		];

		$factory = new EntityChangeFactory(
			new EntityDiffer(),
			new BasicEntityIdParser(),
			$changeClasses,
			EntityChange::class
		);

		return $factory;
	}

	public function newForEntityProvider() {
		return [
			'add item' => [ EntityChange::ADD, new ItemId( 'Q17' ), ItemChange::class ],
			'remove property' => [ EntityChange::REMOVE, new NumericPropertyId( 'P17' ), EntityChange::class ],
		];
	}

	/**
	 * @dataProvider newForEntityProvider
	 *
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $expectedClass
	 */
	public function testNewForEntity( $action, EntityId $entityId, $expectedClass ) {
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
	public function testNewForChangeType( $action, EntityId $entityId, $expectedClass ) {
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$factory = $this->getEntityChangeFactory();

		$change = $factory->newForChangeType( $type, $entityId, [] );
		$this->assertInstanceOf( $expectedClass, $change );
		$this->assertEquals( $type, $change->getType() );
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
	public function testNewFromFieldData( $action, EntityId $entityId, $expectedClass ) {
		$fields = [];
		$fields['type'] = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$fields['object_id'] = $entityId->getSerialization();

		$factory = $this->getEntityChangeFactory();

		$change = $factory->newFromFieldData( $fields );
		$this->assertInstanceOf( $expectedClass, $change );
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
		$this->assertEquals( 'Q1', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~update', $change->getType(), 'type' );

		$this->assertEquals(
			[ 'es' ],
			$change->getCompactDiff()->getLabelChanges(),
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
		$this->assertEquals( 'Q1', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~add', $change->getType(), 'type' );

		$this->assertEquals(
			[ 'en' ],
			$change->getCompactDiff()->getLabelChanges(),
			'diff'
		);
	}

	public function testNewFromUpdate_remove() {
		$propertyId = new NumericPropertyId( 'P2' );

		$property = new Property( $propertyId, null, 'string' );
		$property->setLabel( 'de', 'Katze' );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->newFromUpdate( EntityChange::REMOVE, $property, null );

		$this->assertEquals( $propertyId, $change->getEntityId(), 'entity id' );
		$this->assertEquals( 'P2', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-property~remove', $change->getType(), 'type' );

		$this->assertEquals(
			[ 'de' ],
			$change->getCompactDiff()->getLabelChanges(),
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
		$this->assertEquals( 'Q4', $change->getObjectId(), 'object id' );
		$this->assertEquals( 'wikibase-item~restore', $change->getType(), 'type' );

		$this->assertEquals(
			[ 'enwiki' => [ null, 'Kitten', false ] ],
			$change->getCompactDiff()->getSiteLinkChanges(),
			'diff'
		);
	}

	public function testNewFromUpdate_excludeStatementsInDiffs() {
		$factory = $this->getEntityChangeFactory();

		$item = new Item( new ItemId( 'Q3' ) );
		$statementList = new StatementList(
			new Statement( new PropertyNoValueSnak( 9000 ) )
		);

		$item->setStatements( $statementList );

		$updatedItem = new Item( new ItemId( 'Q3' ) );
		$statementList = new StatementList(
			new Statement( new PropertyNoValueSnak( 10 ) )
		);

		$updatedItem->setStatements( $statementList );

		$change = $factory->newFromUpdate( EntityChange::UPDATE, $item, $updatedItem );

		$this->assertSame(
			[ 'P10', 'P9000' ],
			$change->getCompactDiff()->getStatementChanges(),
			'Diff excludes statement changes and is empty'
		);
	}

}
