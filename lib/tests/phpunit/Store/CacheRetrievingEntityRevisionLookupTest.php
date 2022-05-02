<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\CacheRetrievingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Tests\EntityRevisionLookupTestCase;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Lib\Store\CacheRetrievingEntityRevisionLookup
 *
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class CacheRetrievingEntityRevisionLookupTest extends EntityRevisionLookupTestCase {

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

		return new CacheRetrievingEntityRevisionLookup( new EntityRevisionCache( new HashBagOStuff() ), $mock );
	}

	public function testGetEntityRevision_byRevisionIdWithMode() {
		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock = $this->createMock( EntityRevisionLookup::class );
		$mock->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id, 1234, 'load-mode' )
			->willReturn( $item );

		$lookup = new CacheRetrievingEntityRevisionLookup( new EntityRevisionCache( new HashBagOStuff() ), $mock );
		$lookup->setVerifyRevision( false );

		$this->assertSame(
			$item,
			$lookup->getEntityRevision( $id, 1234, 'load-mode' )
		);
	}

	public function testRetrievingDoesNotWriteToTheCache() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CacheRetrievingEntityRevisionLookup( $entityRevisionCache, $mock );

		$this->assertEquals( $item, $lookup->getEntityRevision( $id )->getEntity(), 'Retrieve' );

		$this->assertNull( $entityRevisionCache->get( $id ), 'Should still not be cached' );
	}

	public function testWithRevisionVerification() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$mock->putEntity( $item, 11 );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CacheRetrievingEntityRevisionLookup( $entityRevisionCache, $mock );
		$lookup->setVerifyRevision( true );

		// Cache the initial version
		$entityRevisionCache->set( new EntityRevision( $item, 11 ) );

		// create new revision
		$mock->putEntity( $item, 12 );

		// make sure we get the new revision automatically
		$revId = $this->extractConcreteRevisionId( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 12, $revId, 'new revision should be detected if verification is enabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 12, $rev->getRevisionId(), 'new revision should be detected if verification is enabled' );

		$mock->removeEntity( $id );

		// try to fetch it again
		$revId = $this->assertNonexistent( $lookup->getLatestRevisionId( $id ), 'deletion should be detected if verification is enabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertNull( $rev, 'deletion should be detected if verification is enabled' );
	}

	public function testWithoutRevisionVerification() {
		$mock = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = new Item( $id );

		$entityRevisionCache = new EntityRevisionCache( new HashBagOStuff() );
		$lookup = new CacheRetrievingEntityRevisionLookup( $entityRevisionCache, $mock );
		$lookup->setVerifyRevision( false );

		// Create and cache new revision
		$mock->putEntity( $item, 12 );
		$entityRevisionCache->set( new EntityRevision( $item, 11 ) );

		// check that we are still getting the old revision
		$revId = $this->extractConcreteRevisionId( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 11, $revId, 'new revision should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevisionId(), 'new revision should be ignored if verification is disabled' );

		$mock->removeEntity( $id );

		// try to fetch it again - should still be cached
		$revId = $this->extractConcreteRevisionId( $lookup->getLatestRevisionId( $id ) );
		$this->assertEquals( 11, $revId, 'deletion should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevisionId(), 'deletion should be ignored if verification is disabled' );
	}

	private function extractConcreteRevisionId( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a concrete revision result given' );
		};

		return $result->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revId ) {
				return $revId;
			} )
			->map();
	}

	private function assertNonexistent( LatestRevisionIdResult $result, $message ) {
		$shouldNotBeCalled = function () use ( $message ) {
			$this->fail( $message );
		};

		return $result->onNonexistentEntity( function () {
				$this->assertTrue( true );
		} )
			->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( $shouldNotBeCalled )
			->map();
	}

}
