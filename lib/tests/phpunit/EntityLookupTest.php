<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Query;
use Wikibase\EntityLookup;
use Wikibase\Property;

use DataTypes\DataTypeFactory;

/**
 * Base class for testing EntityLookup implementations
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityLookupTest extends EntityTestCase {

	/**
	 * @param \Wikibase\Entity[]     $entities
	 *
	 * @todo: Support for multiple revisions per entity.
	 *        Needs a way to return the revision IDs.
	 *
	 * @return EntityLookup
	 */
	protected abstract function newEntityLoader( array $entities );

	/**
	 * @return \Wikibase\Entity[]
	 */
	protected function getTestEntities() {
		static $entities = null;

		if ( $entities === null ) {
			$item = Item::newEmpty();
			$item->setId( 42 );

			$entities[11] = $item;

			$item = $item->copy();
			$item->setLabel( 'en', "Foo" );

			$entities[12] = $item;

			$prop = Property::newEmpty();
			$prop->setId( 753 );
			$prop->setDataTypeId( "string" );

			$entities[13] = $prop;
		}

		return $entities;
	}

	protected function getLookup() {
		$entities = $this->getTestEntities();
		$lookup = $this->newEntityLoader( $entities );

		return $lookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	public static function provideGetEntity() {
		$cases = array(
			array( // #0: any revision
				'q42', 0, true,
			),
			array( // #1: first revision
				'q42', 11, true,
			),
			array( // #2: second revision
				'q42', 12, true,
			),
			array( // #3: bad revision
				'q42', 600000, false, 'Wikibase\StorageException',
			),
			array( // #4: wrong type
				'q753', 0, false,
			),
			array( // #5: bad revision
				'p753', 23, false, 'Wikibase\StorageException',
			),
			array( // #6: some revision
				'p753', 0, true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntity
	 *
	 * @param string|EntityId $id       The entity to get
	 * @param int             $revision The revision to get (or 0)
	 * @param bool            $shouldExist
	 * @param string|null     $expectException
	 */
	public function testGetEntity( $id, $revision, $shouldExist, $expectException = null ) {
		if ( $expectException !== null ) {
			$this->setExpectedException( $expectException );
		}

		if ( is_string( $id ) ) {
			$id = EntityId::newFromPrefixedId( $id );
		}

		$revision = $this->resolveLogicalRevision( $revision );

		$lookup = $this->getLookup();
		$entity = $lookup->getEntity( $id, $revision );

		if ( $shouldExist == true ) {
			$this->assertNotNull( $entity, "ID " . $id->__toString() );
			$this->assertEquals( $id->__toString(), $entity->getId()->__toString() );

			$has = $lookup->hasEntity( $id );
			$this->assertTrue( $has, 'hasEntity' );
		} else {
			$this->assertNull( $entity, "ID " . $id->__toString() );

			if ( $revision == 0 ) {
				$has = $lookup->hasEntity( $id );
				$this->assertFalse( $has, 'hasEntity' );
			}
		}
	}

	public static function provideHasEntity() {
		$cases = array(
			array( // #0
				'q42', true,
			),
			array( // #1
				'q753', false,
			),
			array( // #2
				'p753', true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideHasEntity
	 *
	 * @param string|EntityId $id The entity to check
	 * @param bool $expected
	 */
	public function testHasEntity( $id, $expected ) {
		if ( is_string( $id ) ) {
			$id = EntityId::newFromPrefixedId( $id );
		}

		$lookup = $this->getLookup();
		$result = $lookup->hasEntity( $id );

		$this->assertEquals( $expected, $result );

		$entity = $lookup->getEntity( $id );

		if ( $expected ) {
			$this->assertInstanceOf( 'Wikibase\Entity', $entity );
		} else {
			$this->assertNull( $entity );
		}
	}

	public static function provideGetEntities() {
		return array(
			array( // #0
				array(),
				array(),
				array( new ItemId( 'Q42' ) ),
				array( 'Q42' => new ItemId( 'Q42' ) ),
			),
			array( // #1
				array( new ItemId( 'Q42' ), new ItemId( 'Q33' ) ),
				array( 'Q42' => new ItemId( 'Q42' ), 'Q33' => null ),
				array( new ItemId( 'Q42' ), new PropertyId( 'P753' ), new PropertyId( 'P777' ) ),
				array( 'Q42' => new ItemId( 'Q42' ), 'P753' => new PropertyId( 'P753' ), 'P777' => null ),
			),
		);
	}

	/**
	 * @dataProvider provideGetEntities
	 *
	 * @note check two batches to make sure overlapping batches don't confuse caching.
	 */
	public function testGetEntities( $batch1, $expected1, $batch2, $expected2 ) {
		$lookup = $this->getLookup();

		// check first batch
		$entities1 = $lookup->getEntities( $batch1 );
		$ids1 = $this->getIdsOfEntities( $entities1 );

		$this->assertArrayEquals( $expected1, $ids1, false, true );

		// check second batch
		$entities2 = $lookup->getEntities( $batch2 );
		$ids2 = $this->getIdsOfEntities( $entities2 );

		$this->assertArrayEquals( $expected2, $ids2, false, true );
	}

	protected function getIdsOfEntities( array $entities ) {
		return array_map(
			function( $entity ) {
				return $entity == null ? null : $entity->getId();
			},
			$entities
		);
	}

}

