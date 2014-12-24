<?php

namespace Wikibase\Test;

use ContentHandler;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * Base class for testing EntityRevisionLookup implementations
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class EntityRevisionLookupTest extends \MediaWikiTestCase {

	/**
	 * @return EntityRevision[]
	 */
	protected function getTestRevisions() {
		$entities = array();

		$item = Item::newEmpty();
		$item->setId( 42 );

		$entities[11] = new EntityRevision( $item, 11, '20130101001100' );

		$item = $item->copy();
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
	protected abstract function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects );

	protected function itemSupportsRedirect() {
		if ( !defined( 'CONTENT_MODEL_WIKIBASE_ITEM' ) ) {
			// We currently cannot determine whether redirects are supported if
			// no repo code is available. Just skip the corresponding tests in that case.
			return false;
		}

		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		return $handler->supportsRedirects();
	}

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
				new ItemId( 'q42' ), 600000, false, 'Wikibase\Lib\Store\StorageException',
			),
			array( // #4: wrong type
				new ItemId( 'q753' ), EntityRevisionLookup::LATEST_FROM_SLAVE, false,
			),
			array( // #5: mismatching revision
				new PropertyId( 'p753' ), 11, false, 'Wikibase\Lib\Store\StorageException',
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

	/**
	 * @dataProvider provideGetEntityRevisions
	 *
	 * @param EntityId[] $ids
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER
	 * @param bool[] $exist
	 * @param array $redirectTo
	 */
	public function testGetEntityRevisions( array $ids, $mode, array $exist, array $redirectTo  ) {
		$lookup = $this->getEntityRevisionLookup();
		$result = $lookup->getEntityRevisions( $ids, $mode );

		$i = 0;
		foreach ( $result as $entityRevision ) {
			if ( $exist[$i] ) {
				$this->assertInstanceOf(
					'Wikibase\EntityRevision',
					$entityRevision,
					$ids[$i]->getSerialization() . " should exist"
				);
			} elseif ( !$redirectTo[$i] ) {
				$this->assertSame(
					null,
					$entityRevision,
					$ids[$i]->getSerialization() . " should not exist"
				);
			} else {
				$this->assertSame(
					$entityRevision->getTargetId()->getSerialization(),
					$redirectTo[$i]->getSerialization()
				);
			}

			$i++;
		}
	}

	public static function provideGetEntityRevisions() {
		$cases = array(
			array(
				array( new ItemId( 'q42' ) ),
				EntityRevisionLookup::LATEST_FROM_SLAVE,
				array( true ),
				array( false ),
			),
			array(
				array( new ItemId( 'q42' ), new PropertyId( 'p753' ) ),
				EntityRevisionLookup::LATEST_FROM_SLAVE,
				array( true, true ),
				array( false, false ),
			),
			array(
				array( new ItemId( 'q42' ), new PropertyId( 'p75555553' ) ),
				EntityRevisionLookup::LATEST_FROM_SLAVE,
				array( true, false ),
				array( false, false ),
			),
			array(
				array( new PropertyId( 'p753' ), new ItemId( 'q23' ) ),
				EntityRevisionLookup::LATEST_FROM_SLAVE,
				array( true, false ),
				array( false, new ItemId( 'q42' ) ),
			),
		);

		return $cases;
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
		if ( !$this->itemSupportsRedirect() ) {
			$this->markTestSkipped( 'redirects not supported' );
		}

		$lookup = $this->getEntityRevisionLookup();

		try {
			$lookup->getEntityRevision( $entityId );
			$this->fail( 'Expected an UnresolvedRedirectException exception when looking up a redirect.' );
		} catch ( UnresolvedRedirectException $ex ) {
			$this->assertEquals( $expectedRedirect, $ex->getRedirectTargetId() );
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

	public function provideGetLatestRevisionIds() {
		$cases = array(
			array(
				array( new ItemId( 'q42' ) ),
				array( 'Q42' => 12 ),
			),
			array(
				array(
					new PropertyId( 'p753' ),
					new ItemId( 'q42' )
				),
				array(
					'P753' => 13,
					'Q42' => 12
				),
			),
			array(
				array(
					new PropertyId( 'p753' ),
					new PropertyId( 'p453904583095843095' ),
					new ItemId( 'q42' )
				),
				array(
					'P753' => 13,
					'P453904583095843095' => false,
					'Q42' => 12
				),
			)
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
		$this->assertInstanceOf( 'Wikibase\EntityRevision', $entityRev );
	}

	/**
	 * @dataProvider provideGetLatestRevisionIds
	 *
	 * @param EntityId[] $id
	 * @param mixed $expected
	 */
	public function testGetLatestRevisionIds( array $ids, array $expected ) {
		$lookup = $this->getEntityRevisionLookup();
		$result = $lookup->getLatestRevisionIds( $ids );

		foreach ( $expected as &$foo ) {
			$foo = $this->resolveLogicalRevision( $foo );
		}

		$this->assertSame( $expected, $result );
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

