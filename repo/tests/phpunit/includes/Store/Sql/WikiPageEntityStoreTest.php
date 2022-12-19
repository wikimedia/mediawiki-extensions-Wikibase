<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use ChangeTags;
use Exception;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use RawMessage;
use Serializers\Serializer;
use Status;
use Title;
use User;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\SerializableEntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Content\PropertyContent;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikibase\Repo\Store\Sql\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Store\Sql\WikiPageEntityStore
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageEntityStoreTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	/**
	 * @return EntityHandler
	 */
	private function newCustomEntityHandler() {
		$handler = $this->createMock( EntityHandler::class );

		$handler->method( 'canCreateWithCustomId' )
			->willReturn( true );

		return $handler;
	}

	/**
	 * @param string $idString
	 *
	 * @return EntityId
	 */
	private function newCustomEntityId( $idString ) {
		$id = $this->getMockBuilder( SerializableEntityId::class )
			->setConstructorArgs( [ $idString ] )
			->onlyMethods( [ 'getEntityType', 'serialize', 'unserialize' ] )
			->getMockForAbstractClass();

		$id->method( 'getEntityType' )
			->willReturn( 'custom-type' );

		return $id;
	}

	/**
	 * @return array [ EntityStore, EntityLookup ]
	 */
	protected function createStoreAndLookup() {
		$services = $this->getServiceContainer();

		//NOTE: we want to test integration of WikiPageEntityRevisionLookup and WikiPageEntityStore here!
		$contentCodec = WikibaseRepo::getEntityContentDataCodec( $services );

		$nsLookup = WikibaseRepo::getEntityNamespaceLookup( $services );

		$localSource = new DatabaseEntitySource(
			'local',
			false,
			[
				'item' => [ 'namespaceId' => 5000, 'slot' => SlotRecord::MAIN ],
				'property' => [ 'namespaceId' => 6000, 'slot' => SlotRecord::MAIN ],
			],
			'',
			'',
			'',
			''
		);
		$db = $this->getRepoDomainDb( $this->db );

		$lookup = new WikiPageEntityRevisionLookup(
			new WikiPageEntityMetaDataLookup(
				$nsLookup,
				new EntityIdLocalPartPageTableEntityQuery(
					$nsLookup,
					$services->getSlotRoleStore()
				),
				$localSource,
				$db
			),
			new WikiPageEntityDataLoader( $contentCodec, $services->getBlobStore() ),
			$services->getRevisionStore()
		);

		$store = new WikiPageEntityStore(
			new EntityContentFactory(
				[
					'item' => ItemContent::CONTENT_MODEL_ID,
					'property' => PropertyContent::CONTENT_MODEL_ID,
					'custom-type' => 'wikibase-custom-type',
				],
				[
					'item' => function() {
						return WikibaseRepo::getItemHandler();
					},
					'property' => function() {
						return WikibaseRepo::getPropertyHandler();
					},
					'custom-type' => function() {
						return $this->newCustomEntityHandler();
					},
				]
			),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			new SqlIdGenerator( $db ),
			WikibaseRepo::getEntityIdComposer( $services ),
			$services->getRevisionStore(),
			$localSource,
			$services->getActorNormalization(),
			$services->getPermissionManager(),
			$services->getWatchlistManager(),
			$services->getWikiPageFactory(),
			$db
		);

		return [ $store, $lookup ];
	}

	public function simpleEntityParameterProvider() {
		$item = new Item();
		$item->setLabel( 'en', 'Item' );
		$item->setDescription( 'en', 'Item description' );

		$property = Property::newFromType( 'string' );
		$property->setLabel( 'en', 'Property' );
		$property->setDescription( 'en', 'Property description' );

		return [
			[ $item, new Item() ],
			[ $property, Property::newFromType( 'string' ) ],
		];
	}

	/**
	 * @dataProvider simpleEntityParameterProvider()
	 */
	public function testSaveEntity( EntityDocument $entity, EntityDocument $empty ) {
		/**
		 * @var WikiPageEntityStore $store
		 * @var EntityRevisionLookup $lookup
		 */
		list( $store, $lookup ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		// register mock watcher
		$watcher = $this->createMock( EntityStoreWatcher::class );
		$watcher->expects( $this->exactly( 2 ) )
			->method( 'entityUpdated' );
		$watcher->expects( $this->never() )
			->method( 'redirectUpdated' );

		$store->registerWatcher( $watcher );

		// save entity
		$r1 = $store->saveEntity( $entity, 'create one', $user, EDIT_NEW );
		$entityId = $r1->getEntity()->getId();

		$r1actual = $lookup->getEntityRevision( $entityId );
		$this->assertEquals( $r1->getRevisionId(), $r1actual->getRevisionId(), 'revid' );
		$this->assertEquals( $r1->getTimestamp(), $r1actual->getTimestamp(), 'timestamp' );
		$this->assertEquals( $r1->getEntity()->getId(), $r1actual->getEntity()->getId(), 'entity id' );

		// TODO: check notifications in wb_changes table!

		// update entity
		$empty->setId( $entityId );
		$termLang = 'en';
		$termText = 'UPDATED';
		$empty->getFingerprint()->setLabel( $termLang, $termText );

		$r2 = $store->saveEntity( $empty, 'update one', $user, EDIT_UPDATE, false, [ 'mw-replace' ] );
		$this->assertNotEquals( $r1->getRevisionId(), $r2->getRevisionId(), 'expected new revision id' );

		$r2actual = $lookup->getEntityRevision( $entityId );
		$this->assertEquals( $r2->getRevisionId(), $r2actual->getRevisionId(), 'revid' );
		$this->assertEquals( $r2->getTimestamp(), $r2actual->getTimestamp(), 'timestamp' );
		$this->assertEquals( $r2->getEntity()->getId(), $r2actual->getEntity()->getId(), 'entity id' );

		// check that the tags were applied
		$r2tags = ChangeTags::getTags( $this->db, null, $r2->getRevisionId() );
		$this->assertContains( 'mw-replace', $r2tags );

		// check that the term storage got updated (via a DataUpdate).
		$termLookup = WikibaseRepo::getTermLookup();
		$this->assertSame( $termText, $termLookup->getLabel( $entityId, $termLang ) );
	}

	public function testSaveEntity_invalidContent() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();
		$store = TestingAccessWrapper::newFromObject( $store );

		$user = $this->getTestUser()->getUser();

		$item = new Item();
		$invalidItemContent = $this->createMock( ItemContent::class );
		$invalidItemContent->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( false );

		$contentFactory = $this->createMock( EntityContentFactory::class );

		$contentFactory->expects( $this->once() )
			->method( 'getContentHandlerForType' )
			->with( Item::ENTITY_TYPE )
			->willReturn(
				$store->contentFactory->getContentHandlerForType( Item::ENTITY_TYPE )
			);

		$contentFactory->expects( $this->once() )
			->method( 'newFromEntity' )
			->with( $item )
			->willReturn( $invalidItemContent );

		$store->contentFactory = $contentFactory;

		try {
			$store->saveEntity( $item, 'create one', $user, EDIT_NEW );
		} catch ( StorageException $e ) {
			$status = $e->getStatus();
			$this->assertInstanceOf( Status::class, $status );
			$this->assertTrue( $status->hasMessage( 'invalid-content-data' ) );
			return;
		}
		$this->fail( 'Expected StorageException to be thrown.' );
	}

	public function provideSaveEntityError() {
		$firstItem = new Item();
		$firstItem->setLabel( 'en', 'one' );

		$secondItem = new Item( new ItemId( 'Q768476834' ) );
		$secondItem->setLabel( 'en', 'Bwahahaha' );
		$secondItem->setLabel( 'de', 'Kähähähä' );

		return [
			'not fresh' => [
				'entity' => $firstItem,
				'flags' => EDIT_NEW,
				'baseRevid' => false,
				'error' => StorageException::class,
			],

			'not exists' => [
				'entity' => $secondItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => false,
				'error' => StorageException::class,
			],
		];
	}

	/**
	 * @dataProvider provideSaveEntityError
	 */
	public function testSaveEntityError( EntityDocument $entity, $flags, $baseRevId, $error ) {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		// setup target item
		$one = new Item();
		$one->setLabel( 'en', 'one' );
		$r1 = $store->saveEntity( $one, 'create one', $user, EDIT_NEW );

		// inject ids
		if ( is_int( $baseRevId ) ) {
			// use target item's revision as an offset
			$baseRevId += $r1->getRevisionId();
		}

		if ( $entity->getId() === null ) {
			// use target item's id
			$entity->setId( $r1->getEntity()->getId() );
		}

		// check for error
		$this->expectException( $error );
		$store->saveEntity( $entity, '', $user, $flags, $baseRevId );
	}

	public function testSaveEntity_equalContentYieldsNoEdit() {
		$item = new Item();
		$item->setLabel( 'en', 'ahaha' );

		// This serializer will yield different (but valid) serializations
		// for the same content by appending junk.
		$realSerializer = WikibaseRepo::getStorageEntitySerializer();
		$mockSerializer = $this->createMock( Serializer::class );
		$mockSerializer->method( 'serialize' )
			->willReturnCallback( function( $object ) use ( $realSerializer ) {
				static $c = 0;

				return $realSerializer->serialize( $object )
					+ [ 'serializationArtifact' => $c++ ];
			} );

		$this->setService( 'WikibaseRepo.StorageEntitySerializer', $mockSerializer );

		/**
		 * @var WikiPageEntityStore $store
		 * @var EntityRevisionLookup $lookup
		 */
		list( $store, $lookup ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		// register mock watcher
		$watcher = $this->createMock( EntityStoreWatcher::class );
		$watcher->expects( $this->exactly( 2 ) )
			->method( 'entityUpdated' );
		$watcher->expects( $this->never() )
			->method( 'redirectUpdated' );

		$store->registerWatcher( $watcher );

		$r1 = $store->saveEntity( $item, 'creation', $user, EDIT_NEW );

		// Even though the serialization (and thus the sha1) differs, we
		// don't let the edit through as the underlying content didn't change.
		$r2 = $store->saveEntity( $item, 'null edit', $user, EDIT_UPDATE );

		$this->assertSame( $r1->getRevisionId(), $r2->getRevisionId() );
	}

	public function testSaveRedirect() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		// register mock watcher
		$watcher = $this->createMock( EntityStoreWatcher::class );
		$watcher->expects( $this->once() )
			->method( 'redirectUpdated' );
		$watcher->expects( $this->never() )
			->method( 'entityDeleted' );

		$store->registerWatcher( $watcher );

		// create one
		$one = new Item();
		$termLang = 'en';
		$one->setLabel( $termLang, 'one' );

		$r1 = $store->saveEntity( $one, 'create one', $user, EDIT_NEW );
		$oneId = $r1->getEntity()->getId();

		// redirect one to Q33
		$q33 = new ItemId( 'Q33' );
		$redirect = new EntityRedirect( $oneId, $q33 );

		$redirectRevId = $store->saveRedirect( $redirect, 'redirect one', $user, EDIT_UPDATE, false, [ 'mw-replace' ] );

		// FIXME: use the $lookup to check this, once EntityLookup supports redirects.
		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$revisionRecord = $revisionLookup->getRevisionById( $redirectRevId );

		$this->assertTrue(
			Title::newFromLinkTarget( $revisionRecord->getPageAsLinkTarget() )->isRedirect(),
			'Title::isRedirect'
		);

		$revisionContent = $revisionRecord->getContent( SlotRecord::MAIN );
		$this->assertTrue( $revisionContent->isRedirect(), 'EntityContent::isRedirect()' );
		$this->assertTrue(
			$revisionContent->getEntityRedirect()->equals( $redirect ),
			'getEntityRedirect()'
		);

		$this->assertRedirectPerPage( $q33, $oneId );

		// check that the tags were applied
		$redirectRevTags = ChangeTags::getTags( $this->db, null, $redirectRevId );
		$this->assertContains( 'mw-replace', $redirectRevTags );

		// check that the term storage got updated (via a DataUpdate).
		$termLookup = WikibaseRepo::getTermLookup();
		$this->assertNull( $termLookup->getLabel( $oneId, $termLang ) );

		// TODO: check notifications in wb_changes table!

		// Revert to original content
		$r1 = $store->saveEntity( $one, 'restore one', $user, EDIT_UPDATE );
		$revisionRecord = $revisionLookup->getRevisionById( $r1->getRevisionId() );

		$this->assertFalse(
			Title::newFromLinkTarget( $revisionRecord->getPageAsLinkTarget() )->isRedirect(),
			'Title::isRedirect'
		);
		$this->assertFalse(
			$revisionRecord->getContent( SlotRecord::MAIN )->isRedirect(),
			'EntityContent::isRedirect()'
		);
	}

	private function assertRedirectPerPage( EntityId $expected, EntityId $entityId ) {
		$entityRedirectLookup = WikibaseRepo::getStore()->getEntityRedirectLookup();

		$targetId = $entityRedirectLookup->getRedirectForEntityId( $entityId );

		$this->assertEquals( $expected, $targetId );
	}

	public function unsupportedRedirectProvider() {
		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );

		return [
			'P1 -> P2' => [ new EntityRedirect( $p1, $p2 ) ],
		];
	}

	/**
	 * @dataProvider unsupportedRedirectProvider
	 */
	public function testSaveRedirectFailure( EntityRedirect $redirect ) {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		$this->expectException( StorageException::class );
		$store->saveRedirect( $redirect, 'redirect one', $user, EDIT_UPDATE );
	}

	public function testUserWasLastToEdit() {
		/**
		 * @var WikiPageEntityStore $store
		 * @var EntityRevisionLookup $lookup
		 */
		list( $store, $lookup ) = $this->createStoreAndLookup();

		$anonUser = User::newFromId( 0 );
		$anonUser->setName( '127.0.0.1' );
		$user = $this->getTestUser()->getUser();
		$item = new Item();

		// check for default values, last revision by anon --------------------
		$item->setLabel( 'en', "Test Anon default" );
		$store->saveEntity( $item, 'testing', $anonUser, EDIT_NEW );
		$itemId = $item->getId();

		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$item->setLabel( 'en', "Test SysOp default" );
		$store->saveEntity( $item, 'Test SysOp default', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by anon --------------------
		$item->setLabel( 'en', "Test Anon with user" );
		$store->saveEntity( $item, 'Test Anon with user', $anonUser, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$item->setLabel( 'en', "Test SysOp with user" );
		$store->saveEntity( $item, 'Test SysOp with user', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $user, $itemId, false );
		$this->assertFalse( $res );

		// create an edit and check if the anon user is last to edit --------------------
		$lastRevIdResult = $lookup->getLatestRevisionId(
			$itemId,
			 LookupConstants::LATEST_FROM_MASTER
		);
		$lastRevId = $this->extractConcreteRevisionId( $lastRevIdResult );
		$item->setLabel( 'en', "Test Anon" );
		$store->saveEntity( $item, 'Test Anon', $anonUser, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, $lastRevId );
		$this->assertTrue( $res );
		// also check that there is a failure if we use the sysop user
		$res = $store->userWasLastToEdit( $user, $itemId, $lastRevId );
		$this->assertFalse( $res );

		// create an edit and check if the sysop user is last to edit --------------------
		$lastRevIdResult = $lookup->getLatestRevisionId(
			$itemId,
			 LookupConstants::LATEST_FROM_MASTER
		);
		$lastRevId = $this->extractConcreteRevisionId( $lastRevIdResult );
		$item->setLabel( 'en', "Test SysOp" );
		$store->saveEntity( $item, 'Test SysOp', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $user, $itemId, $lastRevId );
		$this->assertTrue( $res );

		// also check that there is a failure if we use the anon user
		$res = $store->userWasLastToEdit( $anonUser, $itemId, $lastRevId );
		$this->assertFalse( $res );
	}

	public function testUpdateWatchlist() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$user = User::newFromName( "WikiPageEntityStoreTestUser2" );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		$item = new Item();
		$store->saveEntity( $item, 'testing', $user, EDIT_NEW );

		$itemId = $item->getId();

		$store->updateWatchlist( $user, $itemId, true );
		$this->assertTrue( $store->isWatching( $user, $itemId ) );

		$store->updateWatchlist( $user, $itemId, false );
		$this->assertFalse( $store->isWatching( $user, $itemId ) );
	}

	public function testUpdateWatchlist_withoutWatchlistRights() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$user = User::newFromName( "WikiPageEntityStoreTestUser2" );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		$item = new Item();
		$store->saveEntity( $item, 'testing', $user, EDIT_NEW );

		$itemId = $item->getId();

		MediaWikiServices::getInstance()->getPermissionManager()
			->overrideUserRightsForTesting( $user, [ /* no viewmywatchlist, no editmywatchlist */ ] );

		$store->updateWatchlist( $user, $itemId, true );
		$this->assertTrue( $store->isWatching( $user, $itemId ),
			"should allow watching even without editmywatchlist right" );

		$store->updateWatchlist( $user, $itemId, false );
		$this->assertTrue( $store->isWatching( $user, $itemId ),
			"should not allow unwatching without editmywatchlist right" );
	}

	protected function newEntity() {
		$item = new Item();
		return $item;
	}

	/**
	 * Convenience wrapper offering the legacy Status based interface for saving
	 * Entities.
	 *
	 * @todo rewrite the tests using this
	 *
	 * @param WikiPageEntityStore $store
	 * @param EntityDocument $entity
	 * @param string $summary
	 * @param User|null $user
	 * @param int $flags
	 * @param int|bool $baseRevId
	 *
	 * @return Status
	 */
	protected function saveEntity(
		WikiPageEntityStore $store,
		EntityDocument $entity,
		$summary = '',
		User $user = null,
		$flags = 0,
		$baseRevId = false
	) {
		if ( $user === null ) {
			$user = $this->getTestUser()->getUser();
		}

		$revLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		try {
			$rev = $store->saveEntity( $entity, $summary, $user, $flags, $baseRevId );
			$status = Status::newGood( $revLookup->getRevisionById( $rev->getRevisionId() ) );
		} catch ( StorageException $ex ) {
			$status = $ex->getStatus();

			if ( !$status ) {
				$status = Status::newFatal( new RawMessage( $ex->getMessage() ) );
			}
		}

		return $status;
	}

	private function getStatusLine( Status $status ) {
		if ( $status->isGood() ) {
			return '';
		} elseif ( $status->isOK() ) {
			$warnings = $status->getErrorsByType( 'warning' );
			return "\nStatus (OK): Warnings: " . var_export( $warnings );
		} else {
			return "\n" . $status->getWikiText();
		}
	}

	public function testSaveFlags() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$entity = $this->newEntity();
		$prefix = get_class( $this ) . '/';

		// try to create without flags
		$entity->setLabel( 'en', $prefix . 'one' );
		$status = $this->saveEntity( $store, $entity, 'create item' );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-gone-missing' ),
			'try to create without flags, edit gone missing'
		);

		// try to create with EDIT_UPDATE flag
		$entity->setLabel( 'en', $prefix . 'two' );
		$status = $this->saveEntity( $store, $entity, 'create item', null, EDIT_UPDATE );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-gone-missing' ),
			'edit gone missing, try to create with EDIT_UPDATE'
		);

		// try to create with EDIT_NEW flag
		$entity->setLabel( 'en', $prefix . 'three' );
		$status = $this->saveEntity( $store, $entity, 'create item', null, EDIT_NEW );
		$this->assertTrue(
			$status->isOK(),
			'create with EDIT_NEW flag for ' . $entity->getId() .
			$this->getStatusLine( $status )
		);
		$this->assertNotNull( $entity->getId(), 'getEntityId() after save' );

		// ok, the item exists now in the database.

		// try to save with EDIT_NEW flag
		$entity->setLabel( 'en', $prefix . 'four' );
		$status = $this->saveEntity( $store, $entity, 'create item', null, EDIT_NEW );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-already-exists' ),
			'try to save with EDIT_NEW flag, edit already exists'
		);

		// try to save with EDIT_UPDATE flag
		$entity->setLabel( 'en', $prefix . 'five' );
		$status = $this->saveEntity( $store, $entity, 'create item', null, EDIT_UPDATE );
		$this->assertTrue(
			$status->isOK(),
			'try to save with EDIT_UPDATE flag, save failed' . $this->getStatusLine( $status )
		);

		// try to save without flags
		$entity->setLabel( 'en', $prefix . 'six' );
		$status = $this->saveEntity( $store, $entity, 'create item' );
		$this->assertTrue(
			$status->isOK(),
			'try to save without flags, save failed' . $this->getStatusLine( $status )
		);
	}

	/**
	 * @param array $hasSlotCalls Key of slot names mapped to their return value.
	 * @return RevisionRecord
	 */
	private function getMockRevisionRecord( $hasSlotCalls = [] ) {
		$rec = $this->createMock( RevisionRecord::class );
		$rec->method( 'hasSlot' )
			->willReturnCallback( function ( $slotChecked ) use ( $hasSlotCalls ) {
				$this->assertArrayHasKey( $slotChecked, $hasSlotCalls,
					'hasSlot() should be called with one of the expected slot roles' );
				return $hasSlotCalls[$slotChecked];
			} );
		return $rec;
	}

	public function provideAdjustFlagsForMCR() {
		yield 'No flags, results in no adjustments' => [
			0,
			0,
			null,
			SlotRecord::MAIN,
		];
		yield 'UPDATE, with no parent revision, throws exception' => [
			EDIT_UPDATE,
			new StorageException( 'Can\'t perform an update with no parent revision' ),
			null,
			SlotRecord::MAIN,
		];
		yield 'UPDATE, with no slot to update, throws exception' => [
			EDIT_UPDATE,
			new StorageException(
				'Can\'t perform an update when the parent revision doesn\'t have expected slot: main'
			),
			$this->getMockRevisionRecord( [ SlotRecord::MAIN => false ] ),
			SlotRecord::MAIN,
		];
		yield 'NEW, with no parent revision, no adjustments' => [
			EDIT_NEW,
			EDIT_NEW,
			null,
			SlotRecord::MAIN,
		];
		yield 'NEW, with parent revision on main slot, no adjustments' => [
			EDIT_NEW,
			EDIT_NEW,
			$this->getMockRevisionRecord(),
			SlotRecord::MAIN,
		];
		yield 'NEW, with parent revision on non existing extra slot, switch to update' => [
			EDIT_NEW,
			EDIT_UPDATE,
			$this->getMockRevisionRecord( [ 'extra' => false ] ),
			'extra',
		];
		yield 'NEW, with parent revision on existing extra slot, throw exception' => [
			EDIT_NEW,
			new StorageException( 'Can\'t create slot, it already exists: extra' ),
			$this->getMockRevisionRecord( [ 'extra' => true ] ),
			'extra',
		];
	}

	/**
	 * @dataProvider provideAdjustFlagsForMCR
	 * @param int $flagsIn
	 * @param int|Exception $expected
	 * @param RevisionRecord $parentRevision
	 * @param string $slotRole
	 */
	public function testAdjustFlagsForMCR( $flagsIn, $expected, $parentRevision, $slotRole ) {
		$services = $this->getServiceContainer();
		$store = new WikiPageEntityStore(
			$this->createMock( EntityContentFactory::class ),
			$this->createMock( EntityTitleStoreLookup::class ),
			$this->createMock( IdGenerator::class ),
			$this->createMock( EntityIdComposer::class ),
			$this->createMock( RevisionStore::class ),
			new DatabaseEntitySource( 'test', 'testdb', [], '', '', '', '' ),
			$services->getActorNormalization(),
			$services->getPermissionManager(),
			$services->getWatchlistManager(),
			$services->getWikiPageFactory(),
			$this->getRepoDomainDb( $this->db )
		);
		$store = TestingAccessWrapper::newFromObject( $store );

		if ( $expected instanceof Exception ) {
			$this->expectException( get_class( $expected ) );
			$this->expectExceptionMessage( $expected->getMessage() );
		}

		$flagsOut = $store->adjustFlagsForMCR( $flagsIn, $parentRevision, $slotRole );

		$this->assertEquals( $expected, $flagsOut );
	}

	public function testRepeatedSave() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$entity = $this->newEntity();
		$prefix = get_class( $this ) . '/';

		// create
		$entity->setLabel( 'en', $prefix . "First" );
		$status = $this->saveEntity( $store, $entity, 'create item', null, EDIT_NEW );
		$this->assertTrue(
			$status->isOK(),
			'create, save failed, status ok' . $this->getStatusLine( $status )
		);
		$this->assertTrue( $status->isGood(), 'create, status is good' . $this->getStatusLine( $status ) );

		// change
		$prev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$entity->setLabel( 'en', $prefix . "Second" );
		$status = $this->saveEntity( $store, $entity, 'modify item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), 'change, status ok' . $this->getStatusLine( $status ) );
		$this->assertTrue( $status->isGood(), 'change, status good' . $this->getStatusLine( $status ) );

		$rev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$this->assertNotEquals( $prev_id, $rev_id, "revision ID should change on edit" );

		// change again
		$prev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$entity->setLabel( 'en', $prefix . "Third" );
		$status = $this->saveEntity( $store, $entity, 'modify item again', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), 'change again, status ok' . $this->getStatusLine( $status ) );
		$this->assertTrue( $status->isGood(), 'change again, status good' );

		$rev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$this->assertNotEquals( $prev_id, $rev_id, "revision ID should change on edit" );

		// save unchanged
		$prev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$status = $this->saveEntity( $store, $entity, 'save unmodified', null, EDIT_UPDATE );
		$this->assertTrue(
			$status->isOK(),
			'save unchanged, save failed, status ok'
			. $this->getStatusLine( $status )
		);

		$rev_id = $store->getWikiPageForEntity( $entity->getId() )->getLatest();
		$this->assertEquals( $prev_id, $rev_id, "revision ID should stay the same if no change was made" );
	}

	/**
	 * @dataProvider simpleEntityParameterProvider
	 */
	public function testDeleteEntity( EntityDocument $entity ) {
		/**
		 * @var WikiPageEntityStore $store
		 * @var EntityRevisionLookup $lookup
		 */
		list( $store, $lookup ) = $this->createStoreAndLookup();
		$user = $this->getTestUser()->getUser();

		// register mock watcher
		$watcher = $this->createMock( EntityStoreWatcher::class );
		$watcher->expects( $this->once() )
			->method( 'entityDeleted' );

		$store->registerWatcher( $watcher );

		// save entity
		$r1 = $store->saveEntity( $entity, 'create one', $user, EDIT_NEW );
		$entityId = $r1->getEntity()->getId();

		// sanity check
		$this->assertNotNull( $lookup->getEntityRevision( $entityId ) );

		// delete entity
		$store->deleteEntity( $entityId, 'testing', $user );

		// check that it's gone
		$latestRevisionIdResult = $lookup->getLatestRevisionId(
			$entityId,
			 LookupConstants::LATEST_FROM_MASTER
		);
		$this->assertNonexistentRevision( $latestRevisionIdResult );
		$this->assertNull( $lookup->getEntityRevision( $entityId ), 'getEntityRevision' );

		// TODO: check notifications in wb_changes table!

		// check that the term storage got updated (via a DataUpdate).
		$termLookup = WikibaseRepo::getTermLookup();
		try {
			$label = $termLookup->getLabel( $entityId, 'en' );
			$this->assertNull( $label );
		} catch ( TermLookupException $e ) {
			// Expected
		}
	}

	public function provideCanCreateWithCustomId() {
		return [
			'no custom id allowed' => [ new ItemId( 'Q7' ), false ],
			'custom id allowed' => [ $this->newCustomEntityId( 'F7' ), true ],
		];
	}

	/**
	 * @dataProvider provideCanCreateWithCustomId
	 * @covers \Wikibase\Repo\Store\Sql\WikiPageEntityStore::canCreateWithCustomId
	 */
	public function testCanCreateWithCustomId( EntityId $id, $expected ) {
		/** @var WikiPageEntityStore $store */
		$store = $this->createStoreForCustomEntitySource();

		$this->assertSame( $expected, $store->canCreateWithCustomId( $id ), $id->getSerialization() );
	}

	public function testGivenForeignId_canCreateWithCustomIdReturnsFalse() {
		/** @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$this->assertFalse( $store->canCreateWithCustomId( $this->newCustomEntityId( 'foo:F7' ) ) );
	}

	public function testGivenIdOfTypeNotFromTheSource_canCreateWithCustomIdReturnsFalse() {
		$store = $this->createStoreForItemsOnly();

		$this->assertFalse( $store->canCreateWithCustomId( $this->newCustomEntityId( 'F7' ) ) );
	}

	public function testGetWikiPageForEntityFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->getWikiPageForEntity( new NumericPropertyId( 'P42' ) );
	}

	public function testSaveEntityFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->saveEntity(
			new Property( new NumericPropertyId( 'P123' ), null, 'string' ),
			'testing',
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

	public function testDeleteEntityFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->deleteEntity( new NumericPropertyId( 'P123' ), 'testing', $this->getTestUser()->getUser() );
	}

	public function testUserWasLastToEditFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->userWasLastToEdit( $this->getTestUser()->getUser(), new NumericPropertyId( 'P123' ), false );
	}

	public function testSaveRedirectFails_GivenEntityIdFromOtherSource() {
		$source = new NumericPropertyId( 'P123' );
		$target = new NumericPropertyId( 'P321' );

		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->saveRedirect(
			new EntityRedirect( $source, $target ),
			'testing',
			$this->getTestUser()->getUser()
		);
	}

	public function testUpdateWatchListFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->updateWatchlist( $this->getTestUser()->getUser(), new NumericPropertyId( 'P123' ), false );
	}

	public function testIsWatchingFails_GivenEntityIdFromOtherSource() {
		$store = $this->createStoreForItemsOnly();
		$this->expectException( InvalidArgumentException::class );

		$store->isWatching( $this->getTestUser()->getUser(), new NumericPropertyId( 'P123' ) );
	}

	private function createStoreForItemsOnly() {
		$itemSource = new DatabaseEntitySource(
			'local',
			false,
			[ 'item' => [ 'namespaceId' => 5000, 'slot' => SlotRecord::MAIN ] ],
			'',
			'',
			'',
			''
		);
		$db = $this->getRepoDomainDb( $this->db );

		$services = $this->getServiceContainer();
		$store = new WikiPageEntityStore(
			new EntityContentFactory(
				[
					'item' => ItemContent::CONTENT_MODEL_ID,
					'property' => PropertyContent::CONTENT_MODEL_ID,
				],
				[
					'item' => function() {
						return WikibaseRepo::getItemHandler();
					},
					'property' => function() {
						return WikibaseRepo::getPropertyHandler();
					},
				]
			),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			new SqlIdGenerator( $db ),
			WikibaseRepo::getEntityIdComposer( $services ),
			$services->getRevisionStore(),
			$itemSource,
			$services->getActorNormalization(),
			$services->getPermissionManager(),
			$services->getWatchlistManager(),
			$services->getWikiPageFactory(),
			$db
		);

		return $store;
	}

	private function createStoreForCustomEntitySource() {
		$customSource = new DatabaseEntitySource(
			'custom',
			'customdb',
			[ 'custom-type' => [ 'namespaceId' => 666, 'slot' => SlotRecord::MAIN ] ],
			'',
			'',
			'',
			''
		);
		$db = $this->getRepoDomainDb( $this->db );

		$services = $this->getServiceContainer();
		$store = new WikiPageEntityStore(
			new EntityContentFactory(
				[
					'custom-type' => 'wikibase-custom-type',
				],
				[
					'custom-type' => function() {
						return $this->newCustomEntityHandler();
					},
				]
			),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			new SqlIdGenerator( $db ),
			WikibaseRepo::getEntityIdComposer( $services ),
			$services->getRevisionStore(),
			$customSource,
			$services->getActorNormalization(),
			$services->getPermissionManager(),
			$services->getWatchlistManager(),
			$services->getWikiPageFactory(),
			$db
		);

		return $store;
	}

	/**
	 * @param LatestRevisionIdResult $result
	 * @return int
	 */
	private function extractConcreteRevisionId( LatestRevisionIdResult $result ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Expects concrete revision' );
		};

		return $result->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revId ) {
				return $revId;
			} )
			->map();
	}

	/**
	 * @param $latestRevisionIdResult
	 */
	private function assertNonexistentRevision( LatestRevisionIdResult $latestRevisionIdResult ) {
		$shouldNotBeCalled = function () {
			$this->fail( 'Not a nonexistent revision given' );
		};

		$latestRevisionIdResult->onRedirect( $shouldNotBeCalled )
			->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity(
				function () {
					$this->assertTrue( true );
				}
			)->map();
	}

}
