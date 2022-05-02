<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Tests\EntityRevisionLookupTestCase;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Lib\Store\CachingEntityRevisionLookup
 *
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookupTest extends EntityRevisionLookupTestCase {

	/**
	 * @see EntityRevisionLookupTestCase::newEntityRevisionLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ) {
		$mock = new MockRepository();

		foreach ( $entityRevisions as $entityRev ) {
			$mock->putEntity( $entityRev->getEntity(), $entityRev->getRevisionId() );
		}

		foreach ( $entityRedirects as $entityRedir ) {
			$mock->putRedirect( $entityRedir );
		}

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );

		return new CachingEntityRevisionLookup( $entityRevisionCache, $mock );
	}

	public function testGetEntityRevision_byRevisionIdWithMode() {
		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock = $this->createMock( EntityRevisionLookup::class );
		$mock->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id, 1234, 'load-mode' )
			->willReturn( $item );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );

		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );
		$lookup->setVerifyRevision( false );

		$this->assertSame(
			$item,
			$lookup->getEntityRevision( $id, 1234, 'load-mode' )
		);
	}

	public function testWithRevisionVerification() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );

		$lookup->setVerifyRevision( true );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$mock->putEntity( $item, 12 );

		// make sure we get the new revision automatically
		$latestRevisionIdResult = $this->extractConcreteRevision(
			$lookup->getLatestRevisionId( $id )
		);
		$this->assertEquals(
			12,
			$latestRevisionIdResult,
			'new revision should be detected if verification is enabled'
		);

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 12, $rev->getRevisionId(), 'new revision should be detected if verification is enabled' );

		// remove the item
		$mock->removeEntity( $id );

		// try to fetch it again
		$latestRevisionIdResult = $lookup->getLatestRevisionId( $id );
		$this->assertNonexistentRevision(
			$latestRevisionIdResult,
			'deletion should be detected if verification is enabled'
		);

		$rev = $lookup->getEntityRevision( $id );
		$this->assertNull( $rev, 'deletion should be detected if verification is enabled' );
	}

	public function testWithoutRevisionVerification() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );

		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$mock->putEntity( $item, 12 );

		// check that we are still getting the old revision
		$revId = $this->extractConcreteRevision( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 11, $revId, 'new revision should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevisionId(), 'new revision should be ignored if verification is disabled' );

		// remove the item
		$mock->removeEntity( $id );

		// try to fetch it again - should still be cached
		$revId = $this->extractConcreteRevision( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 11, $revId, 'deletion should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevisionId(), 'deletion should be ignored if verification is disabled' );
	}

	public function testEntityUpdated() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );

		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$rev12 = $mock->putEntity( $item, 12 );

		// now, notify the cache
		$lookup->entityUpdated( $rev12 );

		// make sure we get the new revision now
		$revId = $this->extractConcreteRevision( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 12, $revId, 'new revision should be detected after notification' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 12, $rev->getRevisionId(), 'new revision should be detected after notification' );
	}

	public function testRedirectUpdated() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );

		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// replace by a redirect
		$targetId = new ItemId( 'Q222' );
		$redir = new EntityRedirect( $id, $targetId );
		$mock->putRedirect( $redir );

		// now, notify the cache
		$lookup->redirectUpdated( $redir, 17 );

		// make sure we get the new revision now
		try {
			$lookup->getEntityRevision( $id );
			$this->fail( 'UnresolvedRedirectException expected; perhaps the cache did not get purged properly.' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( $targetId, $ex->getRedirectTargetId() );
		}
	}

	public function testEntityDeleted() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CachingEntityRevisionLookup( $entityRevisionCache, $mock );

		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// remove entity
		$mock->removeEntity( $id );

		// now, notify the cache
		$lookup->entityDeleted( $id );

		// make sure we get the new revision now
		$revId = $this->assertNonexistentRevision(
			$lookup->getLatestRevisionId( $id ),
			'deletion should be detected after notification'
		);

		$rev = $lookup->getEntityRevision( $id );
		$this->assertNull( $rev, 'deletion should be detected after notification' );
	}

	private function extractConcreteRevision( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a concrete revision given' );
		};

		return $result->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revId ) {
				return $revId;
			} )
			->map();
	}

	private function assertNonexistentRevision( LatestRevisionIdResult $result, $message ) {
		$shouldNotBeCalled = function () use ( $message ) {
			$this->fail( $message );
		};

		return $result->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity(
				function () {
					$this->assertTrue( true );
				}
			)
			->map();
	}

}
