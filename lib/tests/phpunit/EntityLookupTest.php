<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;

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
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @todo: Support for multiple revisions per entity.
	 *        Needs a way to return the revision IDs.
	 *
	 * @return EntityLookup
	 */
	protected abstract function newEntityLookup( array $entityRevisions, array $entityRedirects );

	/**
	 * @note: not really needed for testing EntityLookup, but makes it easier to
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

	/**
	 * @note: not really needed for testing EntityLookup, but makes it easier to
	 * set up tests for EntityRevisionLookup implementation in a consistent way.
	 *
	 * @return EntityRedirect[]
	 */
	protected function getTestRedirects() {
		static $redirects = null;

		if ( $redirects === null ) {
			// regular redirect
			$redirects[101] = new EntityRedirect( new ItemId( 'Q23' ), new ItemId( 'Q42' ) );

			// double redirect
			$redirects[102] = new EntityRedirect( new ItemId( 'Q6' ), new ItemId( 'Q23' ) );

			// broken redirect
			$redirects[103] = new EntityRedirect( new ItemId( 'Q77' ), new ItemId( 'Q776655' ) );
		}

		return $redirects;
	}

	/**
	 * @return EntityLookup
	 */
	protected function getEntityLookup() {
		$entities = $this->getTestRevisions();
		$redirects = $this->getTestRevisions();
		$lookup = $this->newEntityLookup( $entities, $redirects );

		return $lookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	public static function provideGetEntity() {
		$cases = array(
			array( // #0: any revision
				new ItemId( 'q42' ), true,
			),
			array( // #1: wrong type
				new ItemId( 'q753' ), false,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntity
	 *
	 * @param EntityId $id    The entity to get
	 * @param bool            $shouldExist
	 */
	public function testGetEntity( $id, $shouldExist ) {

		$lookup = $this->getEntityLookup();
		$entity = $lookup->getEntity( $id );

		if ( $shouldExist == true ) {
			$this->assertNotNull( $entity, "ID " . $id->__toString() );
			$this->assertEquals( $id->__toString(), $entity->getId()->__toString() );

			$has = $lookup->hasEntity( $id );
			$this->assertTrue( $has, 'hasEntity' );
		} else {
			$this->assertNull( $entity, "ID " . $id->__toString() );

			$has = $lookup->hasEntity( $id );
			$this->assertFalse( $has, 'hasEntity' );
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

