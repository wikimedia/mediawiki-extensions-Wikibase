<?php

namespace Wikibase\Lib\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * Base class for testing EntityRevisionLookup implementations
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
abstract class EntityRevisionLookupTest extends \MediaWikiTestCase {

	/**
	 * @return EntityRevision[]
	 */
	protected function getTestRevisions() {
		$entities = array();

		$item = new Item( new ItemId( 'Q42' ) );

		$entities[11] = new EntityRevision( $item, 11, '20130101001100' );

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', "Foo" );

		$entities[12] = new EntityRevision( $item, 12, '20130101001200' );

		$prop = Property::newFromType( "string" );
		$prop->setId( 753 );

		$entities[13] = new EntityRevision( $prop, 13, '20130101001300' );

		return $entities;
	}

	/**
	 * @return EntityRedirect[]
	 */
	protected function getTestRedirects() {
		$redirects = array();

		$redirects[] = new EntityRedirect( new ItemId( 'Q23' ), new ItemId( 'Q42' ) );

		return $redirects;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	protected function getEntityRevisionLookup() {
		$revisions = $this->getTestRevisions();
		$redirects = $this->getTestRedirects();

		$lookup = $this->newEntityRevisionLookup( $revisions, $redirects );

		return $lookup;
	}

	/**
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @return EntityRevisionLookup
	 */
	abstract protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects );

	public function provideGetEntityRevision() {
		$cases = array(
			array( // #0: any revision
				new ItemId( 'q42' ), EntityRevisionLookup::LATEST_FROM_SLAVE, true,
			),
			array( // #1: first revision
				new ItemId( 'q42' ), 11, true,
			),
			array( // #2: second revision
				new ItemId( 'q42' ), 12, true,
			),
			array( // #3: bad revision
				new ItemId( 'q42' ), 600000, false, StorageException::class,
			),
			array( // #4: wrong type
				new ItemId( 'q753' ), EntityRevisionLookup::LATEST_FROM_SLAVE, false,
			),
			array( // #5: mismatching revision
				new PropertyId( 'p753' ), 11, false, StorageException::class,
			),
			array( // #6: some revision
				new PropertyId( 'p753' ), EntityRevisionLookup::LATEST_FROM_SLAVE, true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntityRevision
	 *
	 * @param EntityId $id    The entity to get
	 * @param int             $revision The revision to get (or LATEST_FROM_SLAVE or LATEST_FROM_MASTER)
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

	public function provideGetEntityRevision_redirect() {
		$redirects = $this->getTestRedirects();
		$cases = array();

		foreach ( $redirects as $redirect ) {
			$cases[] = array( $redirect->getEntityId(), $redirect->getTargetId() );
		}

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntityRevision_redirect
	 */
	public function testGetEntityRevision_redirect( EntityId $entityId, EntityId $expectedRedirect ) {
		$lookup = $this->getEntityRevisionLookup();

		try {
			$lookup->getEntityRevision( $entityId );
			$this->fail( 'Expected an UnresolvedRedirectException exception when looking up a redirect.' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( $expectedRedirect, $ex->getRedirectTargetId() );
			$this->assertGreaterThan( 0, $ex->getRevisionId() );
			$this->assertNotEmpty( $ex->getRevisionTimestamp() );
		}
	}

	public function provideGetLatestRevisionId() {
		$cases = array(
			array( // #0
				new ItemId( 'q42' ), 12,
			),
			array( // #1
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

		$this->assertInternalType( 'int', $result );
		$this->assertEquals( $expected, $result );

		$entityRev = $lookup->getEntityRevision( $id );
		$this->assertInstanceOf( EntityRevision::class, $entityRev );
	}

	public function testGetLatestRevisionForMissing() {
		$lookup = $this->getEntityRevisionLookup();
		$itemId = new ItemId( 'Q753' );

		$result = $lookup->getLatestRevisionId( $itemId );
		$expected = $this->resolveLogicalRevision( false );

		$this->assertEquals( $expected, $result );

		$entityRev = $lookup->getEntityRevision( $itemId );
		$this->assertNull( $entityRev );
	}

}
