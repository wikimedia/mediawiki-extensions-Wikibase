<?php

namespace Wikibase\Test;

use Wikibase\Content\DeferredDecodingEntityHolder;
use Wikibase\Content\EntityHolder;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Content\DeferredDecodingEntityHolder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseEntity
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class DeferredDecodingEntityHolderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Entity
	 */
	private function newEntity() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'Foo' );

		return $item;
	}

	/**
	 * @param Entity $entity
	 * @param string|null $expectedEntityType
	 * @param EntityId|null $expectedEntityId
	 *
	 * @return EntityHolder
	 */
	private function newHolder( Entity $entity, $expectedEntityType = null, EntityId $expectedEntityId = null ) {
		$codec = new EntityContentDataCodec(
			new BasicEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getInternalEntitySerializer(),
			WikibaseRepo::getDefaultInstance()->getInternalEntityDeserializer()
		);
		$blob = $codec->encodeEntity( $entity, CONTENT_FORMAT_JSON );

		return new DeferredDecodingEntityHolder(
			$codec,
			$blob,
			CONTENT_FORMAT_JSON,
			$expectedEntityType ?: $entity->getType(),
			$expectedEntityId
		);
	}

	public function testGetEntity() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntity();
		$this->assertNotSame( $entity, $actual );
		$this->assertEquals( $entity->getId(), $actual->getId() );
		$this->assertTrue( $entity->equals( $actual ) );
	}

	public function testGetEntityWithExpectedClass() {
		$entity = $this->newEntity();
		$holder = $this->newHolder( $entity );

		$actual = $holder->getEntity( 'Wikibase\DataModel\Entity\Item' );
		$this->assertEquals( $entity, $actual );
	}

	public function testGivenEntityWithoutId_getEntityThrowsException() {
		$holder = $this->newHolder( new Item() );

		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntity();
	}

	public function testGivenMismatchingClassName_secondGetEntityCallThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item );

		$holder->getEntity( 'Wikibase\DataModel\Entity\Item' );
		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntity( 'Wikibase\DataModel\Entity\Property' );
	}

	public function testGivenMismatchingEntityType_getEntityThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'property' );

		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntity();
	}

	public function testGivenMismatchingEntityType_getEntityWithExpectedClassThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'property' );

		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntity( 'Wikibase\DataModel\Entity\Property' );
	}

	public function testGivenMismatchingIds_getEntityThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'item', new ItemId( 'Q42' ) );

		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntity();
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

	public function testGivenMismatchingIds_getEntityIdReturnsGivenId() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'item', new ItemId( 'Q42' ) );

		$actual = $holder->getEntityId();
		$this->assertEquals( new ItemId( 'Q42' ), $actual );
	}

	public function testGivenEntityWithoutId_getEntityIdThrowsException() {
		$holder = $this->newHolder( new Item() );

		$this->setExpectedException( 'RuntimeException' );
		$holder->getEntityId();
	}

}
