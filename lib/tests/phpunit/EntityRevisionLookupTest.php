<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * Base class for testing EntityRevisionLookup implementations
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class EntityRevisionLookupTest extends \PHPUnit_Framework_TestCase {

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

			$prop = Property::newFromType( "string" );
			$prop->setId( 753 );

			$entities[13] = new EntityRevision( $prop, 13, '20130101001300' );
		}

		return $entities;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	protected function getEntityRevisionLookup() {
		$revisions = $this->getTestRevisions();
		$lookup = $this->newEntityRevisionLookup( $revisions );

		return $lookup;
	}

	/**
	 * @param EntityRevision[] $entityRevisions
	 *
	 * @return EntityRevisionLookup
	 */
	protected abstract function newEntityRevisionLookup( array $entityRevisions );

	public static function provideGetEntityRevision() {
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
	 * @dataProvider provideGetEntityRevision
	 *
	 * @param EntityId $id    The entity to get
	 * @param int             $revision The revision to get (or 0)
	 * @param bool            $shouldExist
	 * @param string|null     $expectException
	 */
	public function testGetEntityRevision( $id, $revision, $shouldExist, $expectException = null ) {
		if ( $expectException !== null ) {
			$this->setExpectedException( $expectException );
		}

		$revision = $this->resolveLogicalRevision( $revision );

		$lookup = $this->getEntityRevisionLookup();
		$entityRev = $lookup->getEntityRevision( $id, $revision );

		if ( $shouldExist == true ) {
			$this->assertNotNull( $entityRev, "ID " . $id->__toString() );
			$this->assertEquals( $id->__toString(), $entityRev->getEntity()->getId()->__toString() );
		} else {
			$this->assertNull( $entityRev, "ID " . $id->__toString() );
		}
	}

	public function provideGetLatestRevisionId() {
		$cases = array(
			array( // #0
				new ItemId( 'q42' ), 12,
			),
			array( // #1
				new ItemId( 'q753' ), false,
			),
			array( // #2
				new PropertyId( 'p753' ), 13,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetLatestRevisionId
	 *
	 * @param EntityId $id The entity to check
	 * @param int $expected
	 */
	public function testGetLatestRevisionId( EntityId $id, $expected ) {
		$lookup = $this->getEntityRevisionLookup();
		$result = $lookup->getLatestRevisionId( $id );

		$expected = $this->resolveLogicalRevision( $expected );

		$this->assertEquals( $expected, $result );

		$entityRev = $lookup->getEntityRevision( $id );

		if ( $expected ) {
			$this->assertInstanceOf( 'Wikibase\EntityRevision', $entityRev );
		} else {
			$this->assertNull( $entityRev );
		}
	}

}

