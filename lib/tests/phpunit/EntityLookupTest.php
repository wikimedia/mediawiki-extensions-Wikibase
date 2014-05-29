<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\EntityRevision;

/**
 * Base class for testing EntityLookup implementations
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityLookupTest extends EntityTestCase {

	/**
	 * @param EntityRevision[] $entities
	 *
	 * @todo: Support for multiple revisions per entity.
	 *        Needs a way to return the revision IDs.
	 *
	 * @return EntityLookup
	 */
	protected abstract function newEntityLookup( array $entities );

	/**
	 * @note: not really needed for testing EntityLookup, mut makes it easier to
	 * set up tests for EntityRevisionLookup implementation in a consistent way.
	 *
	 * @return EntityRevision[]
	 */
	protected function getTestRevisions() {
		static $entities = null;

		if ( $entities === null ) {
			$item = Item::newEmpty();
			$item->setId( 42 );

			$entities[11] = new EntityRevision( $item, 11, '20130101001100' );

			$item = $item->copy();
			$item->setLabel( 'en', "Foo" );

			$entities[12] = new EntityRevision( $item, 12, '20130101001200' );

			$prop = Property::newEmpty();
			$prop->setId( 753 );
			$prop->setDataTypeId( "string" );

			$entities[13] = new EntityRevision( $prop, 13, '20130101001300' );
		}

		return $entities;
	}

	/**
	 * @return EntityLookup
	 */
	protected function getEntityLookup() {
		$entities = $this->getTestRevisions();
		$lookup = $this->newEntityLookup( $entities );

		return $lookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	public static function provideGetEntity() {
		$cases = array(
			array( // #0: any revision
				new ItemId( 'q42' ), 0, true,
			),
			array( // #1: first revision
				new ItemId( 'q42' ), 11, true,
			),
			array( // #2: second revision
				new ItemId( 'q42' ), 12, true,
			),
			array( // #3: bad revision
				new ItemId( 'q42' ), 600000, false, 'Wikibase\StorageException',
			),
			array( // #4: wrong type
				new ItemId( 'q753' ), 0, false,
			),
			array( // #5: bad revision
				new PropertyId( 'p753' ), 23, false, 'Wikibase\StorageException',
			),
			array( // #6: some revision
				new PropertyId( 'p753' ), 0, true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntity
	 *
	 * @param EntityId $id    The entity to get
	 * @param int             $revision The revision to get (or 0)
	 * @param bool            $shouldExist
	 * @param string|null     $expectException
	 */
	public function testGetEntity( $id, $revision, $shouldExist, $expectException = null ) {
		if ( $expectException !== null ) {
			$this->setExpectedException( $expectException );
		}

		$revision = $this->resolveLogicalRevision( $revision );

		$lookup = $this->getEntityLookup();
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
				new ItemId( 'q42' ), true,
			),
			array( // #1
				new ItemId( 'q753' ), false,
			),
			array( // #2
				new PropertyId( 'p753' ), true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideHasEntity
	 *
	 * @param EntityId $id The entity to check
	 * @param bool $expected
	 */
	public function testHasEntity( EntityId $id, $expected ) {
		$lookup = $this->getEntityLookup();
		$result = $lookup->hasEntity( $id );

		$this->assertEquals( $expected, $result );

		$entity = $lookup->getEntity( $id );

		if ( $expected ) {
			$this->assertInstanceOf( 'Wikibase\Entity', $entity );
		} else {
			$this->assertNull( $entity );
		}
	}

}

