<?php

namespace Wikibase\Lib\Tests;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * Base class for testing EntityRevisionLookup implementations
 *
 * @covers \Wikibase\Lib\Store\EntityRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class EntityRevisionLookupTestCase extends MediaWikiIntegrationTestCase {

	/**
	 * @return EntityRevision[]
	 */
	protected function getTestRevisions() {
		$entities = [];

		$item = new Item( new ItemId( 'Q42' ) );

		$entities[11] = new EntityRevision( $item, 11, '20130101001100' );

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', "Foo" );

		$entities[12] = new EntityRevision( $item, 12, '20130101001200' );

		$prop = new Property( new NumericPropertyId( 'P753' ), null, 'string' );

		$entities[13] = new EntityRevision( $prop, 13, '20130101001300' );

		return $entities;
	}

	/**
	 * @return EntityRedirect[]
	 */
	protected function getTestRedirects() {
		return [
			new EntityRedirect( new ItemId( 'Q23' ), new ItemId( 'Q42' ) ),
		];
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
		$cases = [
			[ // #0: any revision
				new ItemId( 'q42' ), 0, true,
			],
			[ // #1: first revision
				new ItemId( 'q42' ), 11, true,
			],
			[ // #2: second revision
				new ItemId( 'q42' ), 12, true,
			],
			[ // #3: bad revision
				new ItemId( 'q42' ), 600000, false, StorageException::class,
			],
			[ // #4: wrong type
				new ItemId( 'q753' ), 0, false,
			],
			[ // #5: mismatching revision
				new NumericPropertyId( 'p753' ), 11, false, StorageException::class,
			],
			[ // #6: some revision
				new NumericPropertyId( 'p753' ), 0, true,
			],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntityRevision
	 *
	 * @param EntityId $id    The entity to get
	 * @param int             $revision The revision to get (or 0 for latest)
	 * @param bool            $shouldExist
	 * @param string|null     $expectException
	 */
	public function testGetEntityRevision( EntityId $id, $revision, $shouldExist, $expectException = null ) {
		if ( $expectException !== null ) {
			$this->expectException( $expectException );
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
		foreach ( $this->getTestRedirects() as $redirect ) {
			yield [ $redirect->getEntityId(), $redirect->getTargetId() ];
		}
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
		$cases = [
			[ // #0
				new ItemId( 'q42' ), 12,
			],
			[ // #1
				new NumericPropertyId( 'p753' ), 13,
			],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetLatestRevisionId
	 *
	 * @param EntityId $id The entity to check
	 * @param int $expected
	 */
	public function testGetLatestRevisionId( EntityId $id, $expected ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Should not be called' );
		};

		$lookup = $this->getEntityRevisionLookup();
		$result = $lookup->getLatestRevisionId( $id );
		$gotRevisionId = $result->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revId ) {
				return $revId;
			} )
			->map();

		$expected = $this->resolveLogicalRevision( $expected );

		$this->assertEquals( $expected, $gotRevisionId );

		$entityRev = $lookup->getEntityRevision( $id );
		$this->assertInstanceOf( EntityRevision::class, $entityRev );
	}

	public function testGetLatestRevisionForMissing() {
		$shouldNotBeCalled = function () {
			$this->fail( 'Should not be called' );
		};

		$lookup = $this->getEntityRevisionLookup();
		$itemId = new ItemId( 'Q753' );

		$result = $lookup->getLatestRevisionId( $itemId );
		$gotRevision = $result->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity(
				function () {
					return 'non-existent';
				}
			)
			->onConcreteRevision( $shouldNotBeCalled )
			->map();

		$this->assertEquals( 'non-existent', $gotRevision );

		$entityRev = $lookup->getEntityRevision( $itemId );
		$this->assertNull( $entityRev );
	}

}
