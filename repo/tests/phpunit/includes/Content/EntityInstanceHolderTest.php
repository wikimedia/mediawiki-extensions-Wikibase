<?php

namespace Wikibase\Repo\Tests\Content;

use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Content\EntityInstanceHolder
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityInstanceHolderTest extends \PHPUnit_Framework_TestCase {

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
