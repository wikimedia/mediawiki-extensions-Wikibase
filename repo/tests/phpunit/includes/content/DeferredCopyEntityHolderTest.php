<?php

namespace Wikibase\Test;

use Wikibase\Content\DeferredCopyEntityHolder;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Content\DeferredCopyEntityHolder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseEntity
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DeferredCopyEntityHolderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Entity
	 */
	private function newEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'Foo' );

		return $item;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return EntityHolder
	 */
	private function newHolder( Entity $entity ) {
		$holder = new EntityInstanceHolder( $entity );
		return new DeferredCopyEntityHolder( $holder );
	}

	public function testGetEntity() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntity();
		$this->assertNotSame( $entity, $actual );
		$this->assertEquals( $entity->getId(), $actual->getId() );
		$this->assertEquals( $entity->getLabel( 'en' ), $actual->getLabel( 'en' ) );
	}

	public function testGetEntityType() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntityType();
		$this->assertEquals( $entity->getType(), $actual );
	}

	public function testGetEntityId() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntityId();
		$this->assertEquals( $entity->getId(), $actual );
	}

}
