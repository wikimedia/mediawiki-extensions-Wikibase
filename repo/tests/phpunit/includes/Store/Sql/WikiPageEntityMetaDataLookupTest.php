<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWikiTestCase;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * This test needs to be in repo, although the class is in lib as we can't alter
 * the data without repo functionality.
 *
 * @covers Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup
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
	private function getWikiPageEntityMetaDataLookup() {
		return new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup() );
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
		$lookup = $this->getMockBuilder( WikiPageEntityMetaDataLookup::class )
			->setConstructorArgs( [ $this->getEntityNamespaceLookup() ] )
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
		$db = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
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

	public function testLoadRevisionInformationById_wrongRevision() {
		$entityRevision = $this->data[2];

		$result = $this->getWikiPageEntityMetaDataLookup()
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

	public function testLoadRevisionInformation_unknownNamespace() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = new WikiPageEntityMetaDataLookup( $namespaceLookup );

		\Wikimedia\suppressWarnings(); // suppress warning about entity type with no known namespace
		$result = $metaDataLookup->loadRevisionInformation(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
		\Wikimedia\restoreWarnings();

		$this->assertSame( [ $entityId->getSerialization() => false ], $result );
	}

	public function testGivenEntityFromOtherRepository_loadRevisionInformationThrowsException() {
		$lookup = new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup(), false, '' );

		$this->setExpectedException( InvalidArgumentException::class );

		$lookup->loadRevisionInformation(
			[ new ItemId( 'foo:Q123' ) ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testGivenEntityFromOtherRepository_loadRevisionInformationByRevisionIdThrowsException() {
		$lookup = new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup(), false, '' );

		$this->setExpectedException( InvalidArgumentException::class );

		$lookup->loadRevisionInformationByRevisionId(
			new ItemId( 'foo:Q123' ),
			1,
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
	}

	public function testGivenEntityIdWithRepositoryPrefix_loadRevisionInformationStripsPrefix() {
		$revision = $this->data[0];
		$unprefixedId = $revision->getEntity()->getId()->getSerialization();

		$lookup = new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup(), false, 'foo' );

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

		$lookup = new WikiPageEntityMetaDataLookup( $this->getEntityNamespaceLookup(), false, 'foo' );

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

	public function testLoadLatestRevisionIds_unknownNamespace() {
		$entityId = $this->data[0]->getEntity()->getId();
		$namespaceLookup = new EntityNamespaceLookup( [] );
		$metaDataLookup = new WikiPageEntityMetaDataLookup( $namespaceLookup );

		\Wikimedia\suppressWarnings(); // suppress warning about entity type with no known namespace
		$result = $metaDataLookup->loadLatestRevisionIds(
			[ $entityId ],
			EntityRevisionLookup::LATEST_FROM_REPLICA
		);
		\Wikimedia\restoreWarnings();

		$this->assertSame( [ $entityId->getSerialization() => false ], $result );
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

}
