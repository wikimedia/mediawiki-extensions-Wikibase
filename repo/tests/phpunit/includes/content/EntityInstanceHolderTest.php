<?php

namespace Wikibase\Test;

use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Content\EntityInstanceHolder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseEntity
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityInstanceHolderTest extends \PHPUnit_Framework_TestCase {

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
		return new EntityInstanceHolder( $entity );
	}

	public function testGetEntity() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntity();
		$this->assertSame( $entity, $actual );
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
