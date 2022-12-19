<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\BlobStore;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\DivergingEntityIdException;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\InconsistentRedirectException;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Tests\EntityRevisionLookupTestCase;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
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

	use LocalRepoDbTestHelper;

	/**
	 * @var EntityRevision[]
	 */
	private static $testEntities = [];

	protected function storeTestEntity( EntityDocument $entity ): EntityRevision {
		$store = WikibaseRepo::getEntityStore();
		$revision = $store->saveEntity( $entity, "storeTestEntity", $this->getTestUser()->getUser() );

		return $revision;
	}

	protected function storeTestRedirect( EntityRedirect $redirect ): int {
		$store = WikibaseRepo::getEntityStore();
		$revision = $store->saveRedirect( $redirect, "storeTestEntity", $this->getTestUser()->getUser() );

		return $revision;
	}

	private function getMetaDataLookup(): WikiPageEntityMetaDataLookup {
		$nsLookup = $this->getEntityNamespaceLookup();
		return new WikiPageEntityMetaDataLookup(
			$nsLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$nsLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			new DatabaseEntitySource(
				'test',
				false,
				[
					'item' => [ 'namespaceId' => 1200, 'slot' => SlotRecord::MAIN ],
					'property' => [ 'namespaceId' => 1210, 'slot' => SlotRecord::MAIN ],
				],
				'',
				'',
				'',
				''
			),
			$this->getRepoDomainDb()
		);
	}

	/**
	 * @see EntityRevisionLookupTestCase::newEntityRevisionLookup
	 *
	 * @param EntityRevision[] $entityRevisions
	 * @param EntityRedirect[] $entityRedirects
	 */
	protected function newEntityRevisionLookup( array $entityRevisions, array $entityRedirects ): EntityRevisionLookup {
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
				WikibaseRepo::getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore()
		);
	}

	private function getEntityNamespaceLookup(): EntityNamespaceLookup {
		return WikibaseRepo::getEntityNamespaceLookup();
	}

	protected function resolveLogicalRevision( $revision ): int {
		if ( is_int( $revision ) && isset( self::$testEntities[$revision] ) ) {
			$revision = self::$testEntities[$revision]->getRevisionId();
		}

		return $revision;
	}

	public function testGetEntityRevision_byRevisionIdWithMode(): void {
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
			->willReturn(
				$realMetaDataLookup->loadRevisionInformationByRevisionId( $entityId, $revisionId )
			);

		$lookup = new WikiPageEntityRevisionLookup(
			$metaDataLookup,
			new WikiPageEntityDataLoader(
				WikibaseRepo::getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore()
		);

		$entityRevision = $lookup->getEntityRevision( $entityId, $revisionId, 'load-mode' );

		$this->assertSame( $revisionId, $entityRevision->getRevisionId() );
	}

	public function testGetEntityRevision_fromAlternativeSlot(): void {
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

		$revision = new MutableRevisionRecord( Title::newFromTextThrow( $entityId->getSerialization() ) );
		$revision->setId( $revisionId );
		$revision->setTimestamp( wfTimestampNow() );
		$revision->setSlot( $slot );

		$metaDataLookup = $this->createMock( WikiPageEntityMetaDataLookup::class );

		$metaDataLookup->expects( $this->once() )
			->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityId, $revisionId )
			->willReturn(
				(object)[ 'rev_id' => $revisionId, 'role_name' => 'kittens' ]
			);

		$revisionStore = $this->createMock( RevisionStore::class );

		$revisionStore->expects( $this->once() )
			->method( 'getRevisionById' )
			->with( $revisionId )
			->willReturn( $revision );

		$codec = WikibaseRepo::getEntityContentDataCodec();

		$blobStore = $this->createMock( BlobStore::class );
		$blobStore->expects( $this->once() )
			->method( 'getBlob' )
			->with( 'xx:blob' )
			->willReturn(
				$codec->encodeEntity( $entity, CONTENT_FORMAT_JSON )
			);

		$lookup = new WikiPageEntityRevisionLookup(
			$metaDataLookup,
			new WikiPageEntityDataLoader( $codec, $blobStore ),
			$revisionStore
		);

		$entityRevision = $lookup->getEntityRevision( $entityId, $revisionId );

		$this->assertSame( $revisionId, $entityRevision->getRevisionId() );
	}

	public function testGetLatestRevisionId_Redirect_ReturnsRedirectResultWithCorrectData(): void {
		$entityId = new ItemId( 'Q1' );
		$redirectsTo = new ItemId( 'Q2' );
		$entityRedirect = new EntityRedirect( $entityId, $redirectsTo );

		$redirectRevisionId = $this->storeTestRedirect( $entityRedirect );

		$lookup = new WikiPageEntityRevisionLookup(
			$this->getMetaDataLookup(),
			new WikiPageEntityDataLoader(
				WikibaseRepo::getEntityContentDataCodec(),
				MediaWikiServices::getInstance()->getBlobStore()
			),
			MediaWikiServices::getInstance()->getRevisionStore()
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

	public function testGetEntityRevision_ReturnsNullForNonExistingRevision(): void {
		$entityId = new ItemId( 'Q6654' );

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var MockObject|BlobStore $mockBlobStore */
		$mockBlobStore = $this->createMock( BlobStore::class );

		/** @var MockObject|WikiPageEntityMetaDataAccessor $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor->method( 'loadRevisionInformation' )
			->with( [ $entityId ], LookupConstants::LATEST_FROM_MASTER )
			->willReturn( [ 'Q6654' => false ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore
		);

		$result = $lookup->getEntityRevision( $entityId, 0, LookupConstants::LATEST_FROM_MASTER );
		$this->assertNull( $result );
	}

	public function testGetEntityRevision_ThrowsWhenRequestingSpecificNonExistingEntityRevision(): void {
		$entityId = new ItemId( 'Q6654' );
		$revId = 9876;

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var MockObject|BlobStore $mockBlobStore */
		$mockBlobStore = $this->createMock( BlobStore::class );

		/** @var MockObject|WikiPageEntityMetaDataAccessor $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor->method( 'loadRevisionInformationByRevisionId' )
			->with( $entityId, $revId, LookupConstants::LATEST_FROM_MASTER )
			->willReturn( false );
		$mockMetaDataAccessor = $mockMetaDataAccessor;

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore
		);

		$this->expectException( StorageException::class );
		$lookup->getEntityRevision( $entityId, $revId, LookupConstants::LATEST_FROM_MASTER );
	}

	public function testGetEntityRevision_ThrowsWhenFoundEntityRevisionContainsDivergingEntityId(): void {
		// For all we know this only happened IRL with MediaInfo entities.
		// The following would be MediaInfoId consequently.
		$newEntityId = $this->getMockEntityId( 'M1235' );
		$oldEntityId = $this->getMockEntityId( 'M1234' );
		$oldEntity = $this->createMock( EntityDocument::class );
		$oldEntity
			->method( 'getId' )
			->willReturn( $oldEntityId );
		$revId = 4711;
		$revTimestamp = '20160114180301';
		$lookupMode = LookupConstants::LATEST_FROM_REPLICA;

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

		/** @var MockObject|WikiPageEntityMetaDataAccessor $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor->method( 'loadRevisionInformation' )
			->with( [ $newEntityId ], $lookupMode )
			->willReturn( [ $newEntityId->getSerialization() => (object)[ 'rev_id' => $revId ] ] );

		$entityDataLoader = $this->createMock( WikiPageEntityDataLoader::class );
		$entityDataLoader->method( 'loadEntityDataFromWikiPageRevision' )
			->willReturn( [ new EntityRevision( $oldEntity, $revId, $revTimestamp ), null ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			$entityDataLoader,
			$revisionStore
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

	public function testGetLatestRevisionId_ThrowsWhenFoundRevisionContainsInconsistentRedirectIndication(): void {
		// For all we know this only happened IRL with MediaInfo entities.
		// The following would be MediaInfoId consequently.
		$entityId = $this->getMockEntityId( 'M1234' );
		$entity = $this->createMock( EntityDocument::class );

		$revId = 2000;
		$slotRole = 'mediainfo';
		$lookupMode = LookupConstants::LATEST_FROM_REPLICA;

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

		/** @var MockObject|WikiPageEntityMetaDataAccessor $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor->method( 'loadRevisionInformation' )
			->with( [ $entityId ], $lookupMode )
			->willReturn( [
				$entityId->getSerialization() => (object)[
					'page_latest' => $revId,
					'page_is_redirect' => true,
					'rev_id' => $revId,
					'role_name' => $slotRole,
				],
			] );

		$entityDataLoader = $this->createMock( WikiPageEntityDataLoader::class );
		$entityDataLoader
			->method( 'loadEntityDataFromWikiPageRevision' )
			->willReturn( [ new EntityRevision( $entity, $revId ), null ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			$entityDataLoader,
			$revisionStore
		);

		try {
			$lookup->getLatestRevisionId( $entityId, $lookupMode );
			$this->fail( 'Should throw specific exception' );
		} catch ( InconsistentRedirectException $exception ) {
			$this->assertInstanceOf( InconsistentRedirectException::class, $exception );
			$this->assertSame(
				"Revision '2000' is marked as revision of page redirecting to another," .
				" but no redirect entity data found in slot 'mediainfo'.",
				$exception->getMessage()
			);
			$this->assertSame( $revId, $exception->getRevisionId() );
			$this->assertSame( $slotRole, $exception->getSlotRole() );
		}
	}

	public function testGetLatestRevisionId_ReturnsNullForNonExistingEntityRevision(): void {
		$entityId = new ItemId( 'Q6654' );

		/** @var MockObject|RevisionStore $mockRevisionStore */
		$mockRevisionStore = $this->createMock( RevisionStore::class );

		/** @var MockObject|BlobStore $mockBlobStore */
		$mockBlobStore = $this->createMock( BlobStore::class );

		/** @var MockObject|WikiPageEntityMetaDataAccessor $mockMetaDataAccessor */
		$mockMetaDataAccessor = $this->createMock( WikiPageEntityMetaDataAccessor::class );
		$mockMetaDataAccessor->method( 'loadRevisionInformation' )
			->with( [ $entityId ], LookupConstants::LATEST_FROM_MASTER )
			->willReturn( [ 'Q6654' => false ] );

		$lookup = new WikiPageEntityRevisionLookup(
			$mockMetaDataAccessor,
			new WikiPageEntityDataLoader( WikibaseRepo::getEntityContentDataCodec(), $mockBlobStore ),
			$mockRevisionStore
		);

		$result = $lookup->getLatestRevisionId( $entityId, LookupConstants::LATEST_FROM_MASTER );
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

	private function getMockEntityId( string $idString ) {
		$entityId = $this->createMock( EntityId::class );
		$entityId->method( '__toString' )->willReturn( $idString );
		$entityId->method( 'getSerialization' )->willReturn( $idString );
		$entityId->method( 'equals' )->willReturnCallback(
			function ( EntityId $otherEntityId ) use ( $entityId ) {
				return $otherEntityId->getSerialization() === $entityId->getSerialization();
			}
		);
		return $entityId;
	}

}
