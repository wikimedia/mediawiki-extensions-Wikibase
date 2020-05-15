<?php

namespace Wikibase\Repo\Tests\Content;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Content\DeferredCopyEntityHolder;
use Wikibase\Repo\Content\EntityHolder;
use Wikibase\Repo\Content\EntityInstanceHolder;

/**
 * @covers \Wikibase\Repo\Content\DeferredCopyEntityHolder
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DeferredCopyEntityHolderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return EntityDocument
	 */
	private function newEntity() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'Foo' );

		return $item;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityHolder
	 */
	private function newHolder( EntityDocument $entity ) {
		$holder = new EntityInstanceHolder( $entity );
		return new DeferredCopyEntityHolder( $holder );
	}

	public function testGetEntity() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntity();

		$this->assertNotSame( $entity, $actual );
		$this->assertEquals( $entity->getId(), $actual->getId() );
		$this->assertTrue( $entity->equals( $actual ) );
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
