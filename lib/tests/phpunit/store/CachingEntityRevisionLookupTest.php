<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * @covers Wikibase\Lib\Store\CachingEntityRevisionLookup
 *
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookupTest extends EntityRevisionLookupTest {

	/**
	 * @see EntityLookupTest::newEntityLoader(newEntityLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ) {
		$mockRepository = new MockRepository();

		foreach ( $entityRevisions as $entityRev ) {
			$mockRepository->putEntity( $entityRev->getEntity(), $entityRev->getRevision() );
		}

		foreach ( $entityRedirects as $entityRedir ) {
			$mockRepository->putRedirect( $entityRedir );
		}

		return new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
	}

	public function testWithRevisionVerification() {
		$mockRepository = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = Item::newEmpty();
		$item->setId( $id );

		$mockRepository->putEntity( $item, 11 );

		$lookup = new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
		$lookup->setVerifyRevision( true );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$mockRepository->putEntity( $item, 12 );

		// make sure we get the new revision automatically
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertEquals( 12, $revId, 'new revision should be detected if verification is enabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 12, $rev->getRevision(), 'new revision should be detected if verification is enabled' );

		// remove the item
		$mockRepository->removeEntity( $id );

		// try to fetch it again
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertFalse( $revId, 'deletion should be detected if verification is enabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertNull( $rev, 'deletion should be detected if verification is enabled' );
	}

	public function testWithoutRevisionVerification() {
		$mockRepository = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = Item::newEmpty();
		$item->setId( $id );

		$mockRepository->putEntity( $item, 11 );

		$lookup = new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$mockRepository->putEntity( $item, 12 );

		// check that we are still getting the old revision
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertEquals( 11, $revId, 'new revision should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevision(), 'new revision should be ignored if verification is disabled' );

		// remove the item
		$mockRepository->removeEntity( $id );

		// try to fetch it again - should still be cached
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertEquals( 11, $revId, 'deletion should be ignored if verification is disabled' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 11, $rev->getRevision(), 'deletion should be ignored if verification is disabled' );
	}

	public function testEntityUpdated() {
		$mockRepository = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = Item::newEmpty();
		$item->setId( $id );

		$mockRepository->putEntity( $item, 11 );

		$lookup = new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// create new revision
		$rev12 = $mockRepository->putEntity( $item, 12 );

		// now, notify the cache
		$lookup->entityUpdated( $rev12 );

		// make sure we get the new revision now
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertEquals( 12, $revId, 'new revision should be detected after notification' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertEquals( 12, $rev->getRevision(), 'new revision should be detected after notification' );
	}

	public function testRedirectUpdated() {
		$mockRepository = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = Item::newEmpty();
		$item->setId( $id );

		$mockRepository->putEntity( $item, 11 );

		$lookup = new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// replace by a redirect
		$targetId = new ItemId( 'Q222' );
		$redir = new EntityRedirect( $id, $targetId );
		$mockRepository->putRedirect( $redir );

		// now, notify the cache
		$lookup->redirectUpdated( $redir, 17 );

		// make sure we get the new revision now
		try {
			$lookup->getEntityRevision( $id );
			$this->fail( 'UnresolvedRedirectException expected; perhaps the cache did not get purged properly.' );
		} catch ( UnresolvedRedirectException $ex ) {
			$this->assertEquals( $targetId, $ex->getRedirectTargetId() );
		}
	}

	public function testEntityDeleted() {
		$mockRepository = new MockRepository();

		$id = new ItemId( 'Q123' );
		$item = Item::newEmpty();
		$item->setId( $id );

		$mockRepository->putEntity( $item, 11 );

		$lookup = new CachingEntityRevisionLookup( $mockRepository, new \HashBagOStuff() );
		$lookup->setVerifyRevision( false );

		// fetch first revision, so it gets cached
		$lookup->getEntityRevision( $id );

		// remove entity
		$mockRepository->removeEntity( $id );

		// now, notify the cache
		$lookup->entityDeleted( $id );

		// make sure we get the new revision now
		$revId = $lookup->getLatestRevisionId( $id );
		$this->assertFalse( $revId, 'deletion should be detected after notification' );

		$rev = $lookup->getEntityRevision( $id );
		$this->assertNull( $rev, 'deletion should be detected after notification' );
	}

}
