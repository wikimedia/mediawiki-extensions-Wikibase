<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use stdClass;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\Tests\DataAccessSettingsTest;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * This test needs to be in repo, although the class is in lib as we can't alter
 * the data without repo functionality.
 *
 * @covers \Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityMetaDataLookupTest extends MediaWikiTestCase {

	/**
	 * @var EntityRevision[]
	 */
	private $data = [];

	/**
	 * @var EntityId
	 */
	private $redirectId;

	protected function setUp() {
		parent::setUp();

		if ( !$this->data ) {
			global $wgUser;

			$this->mergeMwGlobalArrayValue(
				'wgWBRepoSettings',
				[ 'entityNamespaces' => [ 'item' => 120, 'property' => 122 ] ]
			);

			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			for ( $i = 0; $i < 3; $i++ ) {
				$this->data[] = $store->saveEntity( new Item(), 'WikiPageEntityMetaDataLookupTest', $wgUser, EDIT_NEW );
			}

			/** @var Item $entity */
			$entity = $this->data[2]->getEntity();
			$entity->getFingerprint()->setLabel( 'en', 'Updated' );
			$this->data[2] = $store->saveEntity( $entity, 'WikiPageEntityMetaDataLookupTest', $wgUser );

			$this->data[] = $store->saveEntity( new Item(), 'WikiPageEntityMetaDataLookupTest', $wgUser, EDIT_NEW );
			$this->redirectId = $this->data[3]->getEntity()->getId();
			$redirect = new EntityRedirect( $this->redirectId, $entity->getId() );
			$this->data[] = $store->saveRedirect( $redirect, 'WikiPageEntityMetaDataLookupTest', $wgUser );
		}
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup;
	}

	/**
	 * @return WikiPageEntityMetaDataLookup
	 */
	private function getWikiPageEntityMetaDataLookup( $nsLookup = null ) {
		if ( $nsLookup === null ) {
			$nsLookup = $this->getEntityNamespaceLookup();
		}
		$slotRoleStore = MediaWikiServices::getInstance()->getSlotRoleStore();

		return new WikiPageEntityMetaDataLookup(
			$nsLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$nsLookup,
				$slotRoleStore
			),
			new UnusableEntitySource(),
			DataAccessSettingsTest::repositoryPrefixBasedFederation()
		);
	}

	private function newMetaDataLookupForSourceBasedFederation( $namespaceLookup = null ) {
		if ( $namespaceLookup === null ) {
			$namespaceLookup = $this->getEntityNamespaceLookup();
		}

		return new WikiPageEntityMetaDataLookup(
			$namespaceLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$namespaceLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			$this->newEntitySource(),
			DataAccessSettingsTest::entitySourceBasedFederation()
		);
	}

	private function newEntitySource() {
		$irrelevantItemNamespaceId = 100;
		$irrelevantPropertyNamespaceId = 200;
		$irrelevantItemSlotName = 'main';
		$irrelevantPropertySlotName = 'main';

		return new EntitySource(
			'testsource',
			false,
			[
				'item' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => $irrelevantItemSlotName ],
				'property' => [ 'namespaceId' => $irrelevantPropertyNamespaceId, 'slot' => $irrelevantPropertySlotName ],
			],
			'',
			''
		);
	}

	/**
	 * This mock uses the real code except for DBAccessBase::getConnection
	 *
	 * @param int $selectCount Number of mocked/lagged DBAccessBase::getConnection::select calls
	 * @param int $selectRowCount Number of mocked/lagged DBAccessBase::getConnection::selectRow calls
	 * @param int $getConnectionCount Number of WikiPageEntityMetaDataLookup::getConnection calls
	 *
	 * @return WikiPageEntityMetaDataLookup
	 */
	private function getLookupWithLaggedConnection( $selectCount, $selectRowCount, $getConnectionCount ) {
		$nsLookup = $this->getEntityNamespaceLookup();
		$lookup = $this->getMockBuilder( WikiPageEntityMetaDataLookup::class )
			->setConstructorArgs( [
				$nsLookup,
				new EntityIdLocalPartPageTableEntityQuery(
					$nsLookup,
					MediaWikiServices::getInstance()->getSlotRoleStore()
				),
				new UnusableEntitySource(),
				DataAccessSettingsTest::repositoryPrefixBasedFederation(),
			] )
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$lookup->expects( $this->exactly( $getConnectionCount ) )
			->method( 'getConnection' )
			->will( $this->returnCallback( function( $id ) use ( $selectCount, $selectRowCount ) {
				$db = $realDB = wfGetDB( DB_MASTER );

				if ( $id === DB_REPLICA ) {
					// This is a (fake) lagged database connection.
					$db = $this->getLaggedDatabase( $realDB, $selectCount, $selectRowCount );
				}

				return $db;
			} ) );

		return $lookup;
	}

	private function newLookupWithLaggedConnectionForSourceBasedFederation( $selectCount, $selectRowCount, $getConnectionCount ) {
		$nsLookup = $this->getEntityNamespaceLookup();
		$lookup = $this->getMockBuilder( WikiPageEntityMetaDataLookup::class )
			->setConstructorArgs( [
				$nsLookup,
				new EntityIdLocalPartPageTableEntityQuery(
					$nsLookup,
					MediaWikiServices::getInstance()->getSlotRoleStore()
				),
				$this->newEntitySource(),
				DataAccessSettingsTest::entitySourceBasedFederation(),
			] )
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$lookup->expects( $this->exactly( $getConnectionCount ) )
			->method( 'getConnection' )
			->will( $this->returnCallback( function( $id ) use ( $selectCount, $selectRowCount ) {
				$db = $realDB = wfGetDB( DB_MASTER );

				if ( $id === DB_REPLICA ) {
					// This is a (fake) lagged database connection.
					$db = $this->getLaggedDatabase( $realDB, $selectCount, $selectRowCount );
				}

				return $db;
			} ) );

		return $lookup;
	}

	/**
	 * Gets a "lagged" database connection: We always leave out the first row on select.
	 */
	private function getLaggedDatabase( IDatabase $realDB, $selectCount, $selectRowCount ) {
		$db = $this->getMockBuilder( IDatabase::class )
			->setMethods( [ 'select', 'selectRow' ] )
			->setProxyTarget( $realDB )
			->getMockForAbstractClass();

		$db->expects( $this->exactly( $selectCount ) )
			->method( 'select' )
			->will( $this->returnCallback( function() use ( $realDB ) {
				// Get the actual result
				$res = call_user_func_array(
					[ $realDB, 'select' ],
					func_get_args()
				);

				// Return the real result minus the first row
				$data = [];
				foreach ( $res as $row ) {
					$data[] = $row;
				}

				return new FakeResultWrapper( array_slice( $data, 1 ) );
			} ) );

		$db->expects( $this->exactly( $selectRowCount ) )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		return $db;
	}

	public function testLoadRevisionInformationById_latest() {
		$entityRevision = $this->data[0];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId( $entityRevision->getEntity()->getId(), $entityRevision ->getRevisionId() );

		$this->assertEquals( $entityRevision->getRevisionId(), $result->rev_id );
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
		$this->assertSame( 'main', $result->role_name );
	}

	public function testLoadRevisionInformationById_latest_entitySourceBasedFederation() {
		$entityRevision = $this->data[0];

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadRevisionInformationByRevisionId( $entityRevision->getEntity()->getId(), $entityRevision ->getRevisionId() );

		$this->assertEquals( $entityRevision->getRevisionId(), $result->rev_id );
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
		$this->assertSame( 'main', $result->role_name );
	}

	public function testLoadRevisionInformationById_masterFallback() {
		$entityRevision = $this->data[0];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->getLookupWithLaggedConnection( 0, 1, 2 );

		$result = $lookup->loadRevisionInformationByRevisionId(
			$entityRevision->getEntity()->getId(),
			$entityRevision ->getRevisionId(),
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertEquals( $entityRevision->getRevisionId(), $result->rev_id );
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_masterFallback_entitySourceBasedFederation() {
		$entityRevision = $this->data[0];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->newLookupWithLaggedConnectionForSourceBasedFederation( 0, 1, 2 );

		$result = $lookup->loadRevisionInformationByRevisionId(
			$entityRevision->getEntity()->getId(),
			$entityRevision ->getRevisionId(),
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertEquals( $entityRevision->getRevisionId(), $result->rev_id );
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_noFallback() {
		$entityRevision = $this->data[0];

		// Should do only one getConnection call.
		$lookup = $this->getLookupWithLaggedConnection( 0, 1, 1 );

		$result = $lookup->loadRevisionInformationByRevisionId(
			$entityRevision->getEntity()->getId(),
			$entityRevision ->getRevisionId(),
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);

		// No fallback: Lagged data is omitted.
		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_noFallback_entitySourceBasedFederation() {
		$entityRevision = $this->data[0];

		// Should do only one getConnection call.
		$lookup = $this->newLookupWithLaggedConnectionForSourceBasedFederation( 0, 1, 1 );

		$result = $lookup->loadRevisionInformationByRevisionId(
			$entityRevision->getEntity()->getId(),
			$entityRevision ->getRevisionId(),
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);

		// No fallback: Lagged data is omitted.
		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_old() {
		$entityRevision = $this->data[2];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() - 1 // There were two edits to this item in sequence
			);

		$this->assertEquals( $entityRevision->getRevisionId() - 1, $result->rev_id );
		// Page latest should reflect that this is not the latest revision
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_old_entitySourceBasedFederation() {
		$entityRevision = $this->data[2];

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() - 1 // There were two edits to this item in sequence
			);

		$this->assertEquals( $entityRevision->getRevisionId() - 1, $result->rev_id );
		// Page latest should reflect that this is not the latest revision
		$this->assertEquals( $entityRevision->getRevisionId(), $result->page_latest );
	}

	public function testLoadRevisionInformationById_wrongRevision() {
		$entityRevision = $this->data[2];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() * 2 // Doesn't exist
			);

		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_wrongRevision_entitySourceBasedFederation() {
		$entityRevision = $this->data[2];

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadRevisionInformationByRevisionId(
				$entityRevision->getEntity()->getId(),
				$entityRevision ->getRevisionId() * 2 // Doesn't exist
			);

		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_notFound() {
		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformationByRevisionId(
				new ItemId( 'Q823487354' ),
				823487354
			);

		$this->assertFalse( $result );
	}

	public function testLoadRevisionInformationById_notFound_entitySourceBasedFederation() {
		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadRevisionInformationByRevisionId(
				new ItemId( 'Q823487354' ),
				823487354
			);

		$this->assertFalse( $result );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param stdClass[] $result
	 */
	private function assertRevisionInformation( array $entityIds, array $result ) {
		$serializedEntityIds = [];
		foreach ( $entityIds as $entityId ) {
			$serializedEntityIds[] = $entityId->getSerialization();
		}

		// Verify that all requested entity ids are part of the result
		$this->assertEquals( $serializedEntityIds, array_keys( $result ) );

		// Verify revision ids
		$this->assertEquals(
			$result[$serializedEntityIds[0]]->rev_id, $this->data[0]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[1]]->rev_id, $this->data[1]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[3]]->rev_id, $this->data[2]->getRevisionId()
		);

		// Verify that no further entities are part of the result
		$this->assertCount( count( $entityIds ), $result );
	}

	public function testLoadRevisionInformation() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadRevisionInformation(
				$entityIds,
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertRevisionInformation( $entityIds, $result );

		$key = $entityIds[0]->getSerialization();
		$this->assertSame( 'main', $result[$key]->role_name );
	}

	public function testLoadRevisionInformation_entitySourceBasedFederation() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadRevisionInformation(
				$entityIds,
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertRevisionInformation( $entityIds, $result );

		$key = $entityIds[0]->getSerialization();
		$this->assertSame( 'main', $result[$key]->role_name );
	}

	public function testLoadRevisionInformation_masterFallback() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->getLookupWithLaggedConnection( 1, 0, 2 );

		$result = $lookup->loadRevisionInformation(
			$entityIds,
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertRevisionInformation( $entityIds, $result );
	}

	public function testLoadRevisionInformation_masterFallback_entitySourceBasedFederation() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->newLookupWithLaggedConnectionForSourceBasedFederation( 1, 0, 2 );

		$result = $lookup->loadRevisionInformation(
			$entityIds,
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertRevisionInformation( $entityIds, $result );
	}

	public function testLoadRevisionInformation_unknownNamespace() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = $this->getWikiPageEntityMetaDataLookup( $namespaceLookup );

		$this->setExpectedException( EntityLookupException::class );
		$metaDataLookup->loadRevisionInformation(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testLoadRevisionInformation_unknownNamespace_entitySourceBasedFederation() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = $this->newMetaDataLookupForSourceBasedFederation( $namespaceLookup );

		$this->setExpectedException( EntityLookupException::class );
		$metaDataLookup->loadRevisionInformation(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testGivenEntityFromOtherRepository_loadRevisionInformationThrowsException() {
		$lookup = $this->getWikiPageEntityMetaDataLookup();

		$this->setExpectedException( InvalidArgumentException::class );

		$lookup->loadRevisionInformation(
			[ new ItemId( 'foo:Q123' ) ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testGivenEntityFromOtherRepository_loadRevisionInformationByRevisionIdThrowsException() {
		$lookup = $this->getWikiPageEntityMetaDataLookup();

		$this->setExpectedException( InvalidArgumentException::class );

		$lookup->loadRevisionInformationByRevisionId(
			new ItemId( 'foo:Q123' ),
			1,
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenEntityFromOtherSource_loadRevisionInformationThrowsException() {
		$lookup = $this->newLookupForEntitySourceProvidingItemsOnly();

		$lookup->loadRevisionInformation(
			[ new PropertyId( 'P123' ) ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenEntityFromOtherSource_loadRevisionInformationByRevisionIdThrowsException() {
		$lookup = $this->newLookupForEntitySourceProvidingItemsOnly();

		$lookup->loadRevisionInformationByRevisionId(
			new PropertyId( 'P123' ),
			1,
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	private function newLookupForEntitySourceProvidingItemsOnly() {
		$irrelevantItemNamespaceId = 100;
		$irrelevantItemSlotName = 'main';

		$itemSource = new EntitySource(
			'testsource',
			false,
			[
				'item' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => $irrelevantItemSlotName ],
			],
			'',
			''
		);

		$namespaceLookup = $this->getEntityNamespaceLookup();
		return new WikiPageEntityMetaDataLookup(
			$namespaceLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$namespaceLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			$itemSource,
			DataAccessSettingsTest::entitySourceBasedFederation()
		);
	}

	public function testGivenEntityIdWithRepositoryPrefix_loadRevisionInformationStripsPrefix() {
		$revision = $this->data[0];
		$unprefixedId = $revision->getEntity()->getId()->getSerialization();

		$nsLookup = $this->getEntityNamespaceLookup();

		$lookup = new WikiPageEntityMetaDataLookup(
			$nsLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$nsLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			new UnusableEntitySource(),
			DataAccessSettingsTest::repositoryPrefixBasedFederation(),
			false,
			'foo'
		);

		$prefixedId = 'foo:' . $unprefixedId;

		$result = $lookup->loadRevisionInformation( [ new ItemId( $prefixedId ) ], EntityRevisionLookup::LATEST_FROM_REPLICA );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( $prefixedId, $result );
		$this->assertEquals(
			$unprefixedId,
			$result[$prefixedId]->page_title
		);
		$this->assertEquals(
			$revision->getRevisionId(),
			$result[$prefixedId]->rev_id
		);
	}

	public function testGivenEntityIdWithRepositoryPrefix_loadRevisionInformationByIdStripsPrefix() {
		$revision = $this->data[0];
		$unprefixedId = $revision->getEntity()->getId()->getSerialization();

		$nsLookup = $this->getEntityNamespaceLookup();
		$lookup = new WikiPageEntityMetaDataLookup(
			$nsLookup,
			new EntityIdLocalPartPageTableEntityQuery(
				$nsLookup,
				MediaWikiServices::getInstance()->getSlotRoleStore()
			),
			new UnusableEntitySource(),
			DataAccessSettingsTest::repositoryPrefixBasedFederation(),
			false,
			'foo'
		);

		$prefixedId = 'foo:' . $unprefixedId;

		$result = $lookup->loadRevisionInformationByRevisionId(
			new ItemId( $prefixedId ),
			$revision->getRevisionId(),
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);

		$this->assertInstanceOf( stdClass::class, $result );
		$this->assertEquals( $revision->getRevisionId(), $result->rev_id );
	}

	/**
	 * @param EntityId[] $entityIds array of four entity IDs; the third entry (index 2) should not exist
	 * @param (int|bool)[] $result
	 */
	private function assertLatestRevisionIds( array $entityIds, array $result ) {
		$serializedEntityIds = [];
		foreach ( $entityIds as $entityId ) {
			$serializedEntityIds[] = $entityId->getSerialization();
		}

		// Verify that all requested entity ids are part of the result
		$this->assertEquals( $serializedEntityIds, array_keys( $result ) );

		// Verify revision ids
		$this->assertEquals(
			$result[$serializedEntityIds[0]], $this->data[0]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[1]], $this->data[1]->getRevisionId()
		);
		$this->assertEquals(
			$result[$serializedEntityIds[3]], $this->data[2]->getRevisionId()
		);

		// Verify that no further entities are part of the result
		$this->assertCount( count( $entityIds ), $result );
	}

	public function testLoadLatestRevisionIds() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadLatestRevisionIds(
				$entityIds,
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertLatestRevisionIds( $entityIds, $result );
	}

	public function testLoadLatestRevisionIds_entitySourceBasedFederation() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadLatestRevisionIds(
				$entityIds,
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertLatestRevisionIds( $entityIds, $result );
	}

	public function testLoadLatestRevisionIds_masterFallback() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->getLookupWithLaggedConnection( 1, 0, 2 );

		$result = $lookup->loadLatestRevisionIds(
			$entityIds,
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertLatestRevisionIds( $entityIds, $result );
	}

	public function testLoadLatestRevisionIds_masterFallback_entitySourceBasedFederation() {
		$entityIds = [
			$this->data[0]->getEntity()->getId(),
			$this->data[1]->getEntity()->getId(),
			new ItemId( 'Q823487354' ), // Doesn't exist
			$this->data[2]->getEntity()->getId()
		];

		// Make sure we have two calls to getConnection: One that asks for a
		// replica and one that asks for the master.
		$lookup = $this->newLookupWithLaggedConnectionForSourceBasedFederation( 1, 0, 2 );

		$result = $lookup->loadLatestRevisionIds(
			$entityIds,
			EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
		);

		$this->assertLatestRevisionIds( $entityIds, $result );
	}

	public function testLoadLatestRevisionIds_unknownNamespace() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = $this->getWikiPageEntityMetaDataLookup( $namespaceLookup );

		$this->setExpectedException( EntityLookupException::class );
		$result = $metaDataLookup->loadLatestRevisionIds(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testLoadLatestRevisionIds_unknownNamespace_entitySourceBasedFederation() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = $this->newMetaDataLookupForSourceBasedFederation( $namespaceLookup );

		$this->setExpectedException( EntityLookupException::class );
		$result = $metaDataLookup->loadLatestRevisionIds(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testLoadLatestRevisionIds_noResultForRedirect() {
		$entityId = $this->redirectId;

		$result = $this->getWikiPageEntityMetaDataLookup()
			->loadLatestRevisionIds(
				[ $entityId ],
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertSame( [ $entityId->getSerialization() => false ], $result );
	}

	public function testLoadLatestRevisionIds_noResultForRedirect_entitySourceBasedFederation() {
		$entityId = $this->redirectId;

		$result = $this->newMetaDataLookupForSourceBasedFederation()
			->loadLatestRevisionIds(
				[ $entityId ],
				EntityRevisionLookup::LATEST_FROM_REPLICA
			);

		$this->assertSame( [ $entityId->getSerialization() => false ], $result );
	}

	public function testGivenEntityFromOtherRepository_loadLatestRevisionIdsThrowsException() {
		$lookup = $this->getWikiPageEntityMetaDataLookup();

		$this->expectException( InvalidArgumentException::class );

		$lookup->loadLatestRevisionIds(
			[ new ItemId( 'foo:Q123' ) ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testGivenEntityFromOtherSource_loadLatestRevisionIdsThrowsException() {
		$lookup = $this->newLookupForEntitySourceProvidingItemsOnly();

		$this->expectException( InvalidArgumentException::class );

		$lookup->loadLatestRevisionIds(
			[ new PropertyId( 'P123' ) ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

}
