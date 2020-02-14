<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Storage\BlobStore;
use MediaWiki\Storage\MutableRevisionRecord;
use MediaWiki\Storage\SlotRecord;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Prophecy\ObjectProphecy;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\InconsistentRedirectException;
use Wikibase\Lib\Store\DivergingEntityIdException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Tests\EntityRevisionLookupTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseEntityLookup
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageEntityRevisionLookupTest extends EntityRevisionLookupTestCase {

	/**
	 * @var EntityRevision[]
	 */
	private static $testEntities = [];

	protected function storeTestEntity( EntityDocument $entity ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveEntity( $entity, "storeTestEntity", $this->getTestUser()->getUser() );

		return $revision;
	}

	protected function storeTestRedirect( EntityRedirect $redirect ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveRedirect( $redirect, "storeTestEntity", $this->getTestUser()->getUser() );

		return $revision;
	}

	private function getMetaDataLookup() {
		$nsLookup = $this->getEntityNamespaceLookup();
		return new WikiPageEntityMetaDataLookup(
			$nsLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$nsLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			new EntitySource(
				'test',
				false,
				[
					'item' => [ 'namespaceId' => 1200, 'slot' => 'main' ],
					'property' => [ 'namespaceId' => 1210, 'slot' => 'main' ],
				],
				'',
				'',
				'',
				''
			)
		);
	}

	/**
	 * @see EntityRevisionLookupTestCase::newEntityRevisionLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ) {
		// make sure all test entities are in the database.

		foreach ( $entityRevisions as $entityRev ) {
			$logicalRev = $entityRev->getRevisionId();

			if ( !isset( self::$testEntities[$logicalRev] ) ) {
				$rev = $this->storeTestEntity( $entityRev->getEntity() );
				self::$testEntities[$logicalRev] = $rev;
			}
		}

		foreach ( $entityRedirects as $entityRedir ) {
			$this->storeTestRedirect( $entityRedir );
		}

		return new WikiPageEntityRevisionLookup(
			$this->getMetaDataLookup(),
			new WikiPageEntityDataLoader(
				WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevisionId();
		}

		return $revision;
	}

	public function testGetEntityRevision_byRevisionIdWithMode() {
		// Needed to fill the database.
		$this->newEntityRevisionLookup( $this->getTestRevisions(), [] );

		$testEntityRevision = reset( self::$testEntities );
		$entityId = $testEntityRevision->getEntity()->getId();
		$revisionId = $testEntityRevision->getRevisionId();

		$realMetaDataLookup = $this->getMetaDataLookup();
		$metaDataLookup = $this->createMock( WikiPageEntityMetaDataLookup::class );

		$metaDataLookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityId, $revisionId, 'load-mode' )
			->will( $this->returnValue(
				$realMetaDataLookup->loadRevisionInformationByRevisionId( $entityId, $revisionId )
			) );

		$lookup = new WikiPageEntityRevisionLookup(
			$metaDataLookup,
			new WikiPageEntityDataLoader(
				WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);

		$entityRevision = $lookup->getEntityRevision( $entityId, $revisionId, 'load-mode' );

		$this->assertSame( $revisionId, $entityRevision->getRevisionId() );
	}

	public function testGetEntityRevision_fromAlternativeSlot() {
		$entity = new Item( new ItemId( 'Q765' ) );
		$entityId = $entity->getId();
		$revisionId = 117;

		$slot = new SlotRecord( (object)[
			'slot_revision_id' => $revisionId,
			'slot_content_id' => 1234567,
			'slot_origin' => 77,
			'content_address' => 'xx:blob',
			'content_format' => CONTENT_FORMAT_JSON,

			// Currently, the model must be ignored. That may change in the future!
			'model_name' => 'WRONG',
			'role_name' => 'kittens',
		], function() {
			// This doesn#t work cross-wiki yet, so make sure we don't try.
			$this->fail( 'Content should not be constructed by the RevisionStore' );
		} );

		$revision = new MutableRevisionRecord( Title::newFromText( $entityId->getSerialization() ) );
		$revision->setId( $revisionId );
		$revision->setTimestamp( wfTimestampNow() );
		$revision->setSlot( $slot );

		$metaDataLookup = $this->createMock( WikiPageEntityMetaDataLookup::class );

		$metaDataLookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityId, $revisionId )
			->will( $this->returnValue(
				(object)[ 'rev_id' => $revisionId, 'role_name' => 'kittens' ]
			) );

		$revisionStore = $this->createMock( RevisionStore::class );

		$revisionStore->expects( $this->once() )
			->method( 'getRevisionById' )
			->with( $revisionId )
			->will( $this->returnValue(
				$revision
			) );

		$codec = WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec();

		$blobStore = $this->createMock( BlobStore::class );
		$blobStore->expects( $this->once() )
			->method( 'getBlob' )
			->with( 'xx:blob' )
			->will( $this->returnValue(
				$codec->encodeEntity( $entity, CONTENT_FORMAT_JSON )
			) );

		$lookup = new WikiPageEntityRevisionLookup(
			$metaDataLookup,
			new WikiPageEntityDataLoader( $codec, $blobStore ),
			$revisionStore,
			false
		);

		$entityRevision = $lookup->getEntityRevision( $entityId, $revisionId );

		$this->assertSame( $revisionId, $entityRevision->getRevisionId() );
	}

	public function testGetLatestRevisionId_Redirect_ReturnsRedirectResultWithCorrectData() {
		$entityId = new ItemId( 'Q1' );
		$redirectsTo = new ItemId( 'Q2' );
		$entityRedirect = new EntityRedirect( $entityId, $redirectsTo );

		$redirectRevisionId = $this->storeTestRedirect( $entityRedirect );

		$lookup = new WikiPageEntityRevisionLookup(
			$this->getMetaDataLookup(),
			new WikiPageEntityDataLoader(
				WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore(),
			false
		);

		$shouldFail = function () {
			$this->fail( 'Expecting redirect revision result' );
		};

		$latestRevisionIdResult = $lookup->getLatestRevisionId( $entityId );
		$gotRevisionId = $latestRevisionIdResult->onConcreteRevision( $shouldFail )
			->onNonexistentEntity( $shouldFail )
			->onRedirect(
				function ( $revisionId, $gotRedirectsTo ) use ( $redirectsTo ) {
					$this->assertEquals( $redirectsTo, $gotRedirectsTo );
					return $revisionId;
				}
			)
			->map();

		$this->assertEquals( $redirectRevisionId, $gotRevisionId );
	}

	public function testGetEntityRevision_ReturnsNullForNonExistingRevision() {
		$entityId = new ItemId( 'Q6654' );

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var BlobStore|ObjectProphecy $mockBlobStore */
		$mockBlobStore = $this->prophesize( BlobStore::class )->reveal();

		/** @var WikiPageEntityMetaDataAccessor|ObjectProphecy $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor
			->loadRevisionInformation( [ $entityId ], EntityRevisionLookup::LATEST_FROM_MASTER )
			->willReturn( [ 'Q6654' => false ] );
		$mockMetaDataAccessor = $mockMetaDataAccessor->reveal();

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore,
			false
		);

		$result = $lookup->getEntityRevision( $entityId, 0, EntityRevisionLookup::LATEST_FROM_MASTER );
		$this->assertNull( $result );
	}

	public function testGetEntityRevision_ThrowsWhenRequestingSpecificNonExistingEntityRevision() {
		$entityId = new ItemId( 'Q6654' );
		$revId = 9876;

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var BlobStore|ObjectProphecy $mockBlobStore */
		$mockBlobStore = $this->prophesize( BlobStore::class )->reveal();

		/** @var WikiPageEntityMetaDataAccessor|ObjectProphecy $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor
			->loadRevisionInformationByRevisionId( $entityId, $revId, EntityRevisionLookup::LATEST_FROM_MASTER )
			->willReturn( false );
		$mockMetaDataAccessor = $mockMetaDataAccessor->reveal();

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore,
			false
		);

		$this->expectException( StorageException::class );
		$lookup->getEntityRevision( $entityId, $revId, EntityRevisionLookup::LATEST_FROM_MASTER );
	}

	public function testGetEntityRevision_ThrowsWhenFoundEntityRevisionContainsDivergingEntityId() {
		// For all we know this only happened IRL with MediaInfo entities.
		// The following would be MediaInfoId consequently.
		$newEntityId = $this->getMockEntityId( 'M1235' );
		$oldEntityId = $this->getMockEntityId( 'M1234' );
		$oldEntity = $this->createMock( EntityDocument::class );
		$oldEntity
			->method( 'getId' )
			->willReturn( $oldEntityId );
		$revId = 4711;
		$revTimestamp = 20160114180301;
		$lookupMode = EntityRevisionLookup::LATEST_FROM_REPLICA;

		$slotRecord = $this->createMock( SlotRecord::class );

		$revision = $this->createMock( RevisionRecord::class );
		$revision
			->method( 'hasSlot' )
			->willReturn( true );
		$revision
			->method( 'audienceCan' )
			->willReturn( true );
		$revision
			->method( 'getSlot' )
			->willReturn( $slotRecord );
		$revision
			->method( 'getId' )
			->willReturn( $revId );
		$revision
			->method( 'getTimestamp' )
			->willReturn( $revTimestamp );

		$revisionStore = $this->createMock( RevisionStore::class );

		$revisionStore->expects( $this->once() )
			->method( 'getRevisionById' )
			->with( $revId )
			->willReturn( $revision );

		/** @var WikiPageEntityMetaDataAccessor|ObjectProphecy $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor
			->loadRevisionInformation( [ $newEntityId ], $lookupMode )
			->willReturn( [ $newEntityId->getSerialization() => (object)[ 'rev_id' => $revId ] ] );
		$mockMetaDataAccessor = $mockMetaDataAccessor->reveal();

		$entityDataLoader = $this->createMock( WikiPageEntityDataLoader::class );
		$entityDataLoader->method( 'loadEntityDataFromWikiPageRevision' )
			->willReturn( [ new EntityRevision( $oldEntity, $revId, $revTimestamp ), null ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			$entityDataLoader,
			$revisionStore,
			false
		);

		try {
			$lookup->getEntityRevision( $newEntityId, 0, $lookupMode );
			$this->fail( 'Should throw specific exception' );
		} catch ( DivergingEntityIdException $exception ) {
			$this->assertInstanceOf( DivergingEntityIdException::class, $exception );
			$this->assertSame(
				'Revision 4711 belongs to M1234 instead of expected M1235',
				$exception->getMessage()
			);
			$this->assertSame( $oldEntity, $exception->getEntityRevision()->getEntity() );
		}
	}

	public function testGetLatestRevisionId_ThrowsWhenFoundRevisionContainsInconsistentRedirectIndication() {
		// For all we know this only happened IRL with MediaInfo entities.
		// The following would be MediaInfoId consequently.
		$entityId = $this->getMockEntityId( 'M1234' );
		$entity = $this->createMock( EntityDocument::class );

		$revId = 2000;
		$slotRole = 'mediainfo';
		$lookupMode = EntityRevisionLookup::LATEST_FROM_REPLICA;

		$slotRecord = $this->createMock( SlotRecord::class );

		$revision = $this->createMock( RevisionRecord::class );
		$revision
			->method( 'hasSlot' )
			->willReturn( true );
		$revision
			->method( 'audienceCan' )
			->willReturn( true );
		$revision
			->method( 'getSlot' )
			->willReturn( $slotRecord );
		$revision
			->method( 'getId' )
			->willReturn( $revId );
		$revision
			->method( 'getTimestamp' )
			->willReturn( 20160114180301 );

		$revisionStore = $this->createMock( RevisionStore::class );

		$revisionStore
			->method( 'getRevisionById' )
			->willReturn( $revision );

		/** @var WikiPageEntityMetaDataAccessor|ObjectProphecy $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor
			->loadRevisionInformation( [ $entityId ], $lookupMode )
			->willReturn( [
				$entityId->getSerialization() => (object)[
					'page_latest' => $revId,
					'page_is_redirect' => true,
					'rev_id' => $revId,
					'role_name' => $slotRole,
				]
			] );
		$mockMetaDataAccessor = $mockMetaDataAccessor->reveal();

		$entityDataLoader = $this->createMock( WikiPageEntityDataLoader::class );
		$entityDataLoader
			->method( 'loadEntityDataFromWikiPageRevision' )
			->willReturn( [ new EntityRevision( $entity, $revId ), null ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			$entityDataLoader,
			$revisionStore,
			false
		);

		try {
			$lookup->getLatestRevisionId( $entityId, $lookupMode );
			$this->fail( 'Should throw specific exception' );
		} catch ( InconsistentRedirectException $exception ) {
			$this->assertInstanceOf( InconsistentRedirectException::class, $exception );
			$this->assertSame(
				"Revision '2000' is marked as revision of page redirecting to another, but no redirect entity data found in slot 'mediainfo'.",
				$exception->getMessage()
			);
			$this->assertSame( $revId, $exception->getRevisionId() );
			$this->assertSame( $slotRole, $exception->getSlotRole() );
		}
	}

	public function testGetLatestRevisionId_ReturnsNullForNonExistingEntityRevision() {
		$entityId = new ItemId( 'Q6654' );

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var BlobStore|ObjectProphecy $mockBlobStore */
		$mockBlobStore = $this->prophesize( BlobStore::class )->reveal();

		/** @var WikiPageEntityMetaDataAccessor|ObjectProphecy $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->prophesize( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor
			->loadRevisionInformation( [ $entityId ], EntityRevisionLookup::LATEST_FROM_MASTER )
			->willReturn( [ 'Q6654' => false ] );
		$mockMetaDataAccessor = $mockMetaDataAccessor->reveal();

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore,
			false
		);

		$result = $lookup->getLatestRevisionId( $entityId, EntityRevisionLookup::LATEST_FROM_MASTER );
		$result->onNonexistentEntity(
				function () {
					$this->assertTrue( true );
				}
			)->onRedirect(
				function () {
					$this->fail( 'Result should trigger onNonexistentEntity' );
				}
			)->onConcreteRevision(
				function () {
					$this->fail( 'Result should trigger onNonexistentEntity' );
				}
			)->map();
	}

	private function getMockEntityId( $idString ) {
		$entityId = $this->createMock( EntityId::class );
		$entityId->method( '__toString' )->willReturn( $idString );
		$entityId->method( 'getSerialization' )->willReturn( $idString );
		$entityId->method( 'equals' )->will(
			$this->returnCallback( function ( EntityId $otherEntityId ) use ( $entityId ) {
				return $otherEntityId->getSerialization() === $entityId->getSerialization();
			} )
		);
		return $entityId;
	}

}
