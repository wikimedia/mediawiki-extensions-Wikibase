<?php

namespace Wikibase\Repo\Tests\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Repo\Content\DeferredDecodingEntityHolder;
use Wikibase\Repo\Content\EntityHolder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Content\DeferredDecodingEntityHolder
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class DeferredDecodingEntityHolderTest extends \PHPUnit\Framework\TestCase {

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
	 * @param string|null $expectedEntityType
	 * @param EntityId|null $expectedEntityId
	 *
	 * @return EntityHolder
	 */
	private function newHolder( EntityDocument $entity, $expectedEntityType = null, EntityId $expectedEntityId = null ) {
		$codec = new EntityContentDataCodec(
			new ItemIdParser(),
			WikibaseRepo::getStorageEntitySerializer(),
			WikibaseRepo::getInternalFormatEntityDeserializer()
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

		$actual = $holder->getEntity( Item::class );
		$this->assertEquals( $entity, $actual );
	}

	public function testGivenEntityWithoutId_getEntityThrowsException() {
		$holder = $this->newHolder( new Item() );

		$this->expectException( RuntimeException::class );
		$holder->getEntity();
	}

	public function testGivenMismatchingClassName_secondGetEntityCallThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item );

		$holder->getEntity( Item::class );
		$this->expectException( RuntimeException::class );
		$holder->getEntity( Property::class );
	}

	public function testGivenMismatchingEntityType_getEntityThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'property' );

		$this->expectException( RuntimeException::class );
		$holder->getEntity();
	}

	public function testGivenMismatchingEntityType_getEntityWithExpectedClassThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'property' );

		$this->expectException( RuntimeException::class );
		$holder->getEntity( Property::class );
	}

	public function testGivenMismatchingIds_getEntityThrowsException() {
		$item = $this->newEntity();
		$holder = $this->newHolder( $item, 'item', new ItemId( 'Q42' ) );

		$this->expectException( RuntimeException::class );
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

		$this->expectException( RuntimeException::class );
		$holder->getEntityId();
	}

}
