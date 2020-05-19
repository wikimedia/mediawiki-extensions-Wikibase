<?php

namespace Wikibase\Repo\Tests\Content;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Content\EntityInstanceHolder;

/**
 * @covers \Wikibase\Repo\Content\EntityInstanceHolder
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityInstanceHolderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return EntityDocument
	 */
	private function newEntity() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'Foo' );

		return $item;
	}

	public function testGetEntity() {
		$entity = $this->newEntity();
		$holder = new EntityInstanceHolder( $entity );

		$actual = $holder->getEntity();
		$this->assertSame( $entity, $actual );
	}

	public function testGetEntityType() {
		$entity = $this->newEntity();
		$holder = new EntityInstanceHolder( $entity );

		$actual = $holder->getEntityType();
		$this->assertEquals( $entity->getType(), $actual );
	}

	public function testGetEntityId() {
		$entity = $this->newEntity();
		$holder = new EntityInstanceHolder( $entity );

		$actual = $holder->getEntityId();
		$this->assertEquals( $entity->getId(), $actual );
	}

}
