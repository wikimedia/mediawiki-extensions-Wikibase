<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Store\Sql\SqlChangeStore;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\SqlChangeStore
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlChangeStoreTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function setUp(): void {
		parent::setUp();
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes table." );
		}
		$this->db->delete( 'wb_changes', '*', __METHOD__ );
		$this->tablesUsed[] = 'wb_changes';
	}

	private function newSqlChangeStore(): SqlChangeStore {
		return new SqlChangeStore( $this->getRepoDomainDb() );
	}

	public function saveChangeInsertProvider() {
		$factory = $this->getEntityChangeFactory();

		$time = wfTimestamp( TS_MW );

		$simpleChange = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );

		$changeWithDiff = $factory->newForEntity( EntityChange::REMOVE, new ItemId( 'Q42' ) );
		$changeWithDiff->setField( 'time', $time );
		$changeWithDiff->setCompactDiff( ( new EntityDiffChangedAspectsFactory() )->newEmpty() );

		$changeWithDataFromRC = $factory->newForEntity( EntityChange::REMOVE, new ItemId( 'Q123' ) );
		// the fields and metadata mirror those added in RecentChangeSaveHookHandler
		$changeWithDataFromRC->setFields( [
			'revision_id' => 343,
			'time' => $time,
			'user_id' => 34,
		] );
		$changeWithDataFromRC->setMetadata( [
			'bot' => 0,
			'page_id' => 2354,
			'rev_id' => 343,
			'parent_id' => 897,
			'comment' => 'Fake data!',
			'user_text' => 'BlackMagicIsEvil',
			'central_user_id' => 9,
		] );

		return [
			'Simple change' => [
				[
					'change_type' => 'wikibase-item~add',
					'change_time' => $time,
					'change_object_id' => 'Q21389475',
					'change_revision_id' => '0',
					'change_user_id' => '0',
					'change_info' => '[]',
				],
				$simpleChange,
			],
			'Change with a diff' => [
				[
					'change_type' => 'wikibase-item~remove',
					'change_time' => $time,
					'change_object_id' => 'Q42',
					'change_revision_id' => '0',
					'change_user_id' => '0',
					'change_info' => '{"compactDiff":"{\"arrayFormatVersion\":1,' .
						'\"labelChanges\":[],\"descriptionChanges\":[],\"statementChanges\":[],' .
						'\"siteLinkChanges\":[],\"otherChanges\":false}"}',
				],
				$changeWithDiff,
			],
			'Change with data from RC' => [
				[
					'change_type' => 'wikibase-item~remove',
					'change_time' => $time,
					'change_object_id' => 'Q123',
					'change_revision_id' => '343',
					'change_user_id' => '34',
					'change_info' => '{"metadata":{"bot":0,"page_id":2354,"rev_id":343,' .
						'"parent_id":897,"comment":"Fake data!","user_text":"BlackMagicIsEvil","central_user_id":9}}',
				],
				$changeWithDataFromRC,
			],
		];
	}

	/**
	 * @dataProvider saveChangeInsertProvider
	 */
	public function testSaveChange_insert( array $expected, EntityChange $change ) {
		$store = $this->newSqlChangeStore();
		$store->saveChange( $change );

		$res = $this->db->select( 'wb_changes', '*', [], __METHOD__ );

		$this->assertSame( 1, $res->numRows(), 'row count' );

		$row = (array)$res->current();
		$this->assertTrue( is_numeric( $row['change_id'] ) );

		$this->assertEqualsWithDelta(
			// wfTimestamp returns string, assertEqualsWithDelta requires int
			(int)wfTimestamp( TS_UNIX, $expected['change_time'] ),
			(int)wfTimestamp( TS_UNIX, $row['change_time'] ),
			60 * 60, // 1 hour
			'Change time'
		);

		unset( $row['change_id'] );
		unset( $row['change_time'] );
		unset( $expected['change_time'] );

		$this->assertSame( $expected, $row );

		$this->assertIsInt( $change->getId() );
	}

	public function testSaveChange_update() {
		$factory = $this->getEntityChangeFactory();
		$change = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );
		$change->setField( 'time', wfTimestampNow() );

		$store = $this->newSqlChangeStore();
		$store->saveChange( $change );
		$expected = [
			'change_id' => (string)$change->getId(),
			'change_type' => 'wikibase-item~add',
			'change_time' => $this->db->timestamp( '20121026200049' ),
			'change_object_id' => 'Q21389475',
			'change_revision_id' => '0',
			'change_user_id' => '0',
			'change_info' => '[]',
		];

		$change->setField( 'time', '20121026200049' );
		$store->saveChange( $change );

		$res = $this->db->select( 'wb_changes', '*', [], __METHOD__ );

		$this->assertSame( 1, $res->numRows(), 'row count' );

		$row = (array)$res->current();

		$this->assertSame( $expected, $row );
	}

	public function testDeleteChangesByChangeIds(): void {
		$factory = $this->getEntityChangeFactory();
		$change = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );
		$store = $this->newSqlChangeStore();
		$store->saveChange( $change );

		$store->deleteChangesByChangeIds( [ $change->getId() ] );

		$res = $this->db->select( 'wb_changes', '*', [], __METHOD__ );
		$this->assertSame( 0, $res->numRows(), 'row count' );
	}

	private function getEntityChangeFactory() {
		$changeClasses = [
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		];

		return new EntityChangeFactory(
			new EntityDiffer(),
			new ItemIdParser(),
			$changeClasses
		);
	}
}
