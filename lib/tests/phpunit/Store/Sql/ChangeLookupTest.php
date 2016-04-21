<?php

namespace Wikibase\Test;

use Wikibase\Change;
use Wikibase\EntityChange;
use Wikibase\Lib\Store\ChangeLookup;
use Wikibase\Repo\Store\Sql\SqlChangeStore;

/**
 * @covers Wikibase\Lib\Store\ChangeLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class ChangeLookupTest extends \MediaWikiTestCase {

	public function testGetRecordId() {
		$change = $this->getMock( Change::class );
		$change->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 42 ) );

		$changeLookup = new ChangeLookup( [], 'doesntmatterwiki' );

		$this->assertSame( 42, $changeLookup->getRecordId( $change ) );
	}

	public function loadChunkProvider() {
		list( $changeOne, $changeTwo, $changeThree ) = $this->getEntityChanges();

		return array(
			'Get one change' => array(
				array( $changeOne ),
				array( $changeThree, $changeTwo, $changeOne ),
				3,
				1
			),
			'Get two changes, with offset' => array(
				array( $changeOne, $changeTwo ),
				array( $changeTwo, $changeTwo, $changeTwo ),
				3,
				2
			),
			'Ask for six changes (get two), with offset' => array(
				array( $changeTwo, $changeThree ),
				array( $changeThree ),
				6,
				100
			)
		);
	}

	/**
	 * @dataProvider loadChunkProvider
	 */
	public function testLoadChunk( array $expected, array $changesToStore, $start, $size ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes table." );
		}

		$changeStore = new SqlChangeStore( wfGetLB() );
		foreach ( $changesToStore as $change ) {
			$change->setField( 'id', null ); // Null the id as we save the same changes multiple times
			$changeStore->saveChange( $change );
		}
		$start = $this->offsetStart( $start );

		$lookup = new ChangeLookup(
			array( 'wikibase-item~remove' => EntityChange::class ),
			wfWikiID()
		);

		$changes = $lookup->loadChunk( $start, $size );

		$this->assertChangesEqual( $expected, $changes, $start );
	}

	/**
	 * @depends testLoadChunk
	 */
	public function testLoadByChangeIds() {
		$start = $this->offsetStart( 3 );

		$lookup = new ChangeLookup(
			array( 'wikibase-item~remove' => EntityChange::class ),
			wfWikiID()
		);

		$changes = $lookup->loadByChangeIds( array( $start, $start + 1, $start + 4 ) );
		list( $changeOne, $changeTwo, $changeThree ) = $this->getEntityChanges();

		$this->assertChangesEqual(
			array(
				$changeOne, $changeTwo, $changeThree
			),
			$changes,
			$start
		);
	}

	public function testLoadByRevisionId() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes table." );
		}

		list( $expected ) = $this->getEntityChanges();
		$expected->setField( 'revision_id', 342342 );
		$expected->setField( 'id', null ); // Null the id as we save the same changes multiple times

		$changeStore = new SqlChangeStore( wfGetLB() );
		$changeStore->saveChange( $expected );

		$lookup = new ChangeLookup(
			array( 'wikibase-item~remove' => EntityChange::class ),
			wfWikiID()
		);

		$change = $lookup->loadByRevisionId( 342342 );

		$this->assertChangesEqual(
			array( $expected ),
			array( $change )
		);
	}

	public function testLoadByRevisionId_notFound() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes table." );
		}

		$lookup = new ChangeLookup(
			array( 'wikibase-item~remove' => EntityChange::class ),
			wfWikiID()
		);

		$changes = $lookup->loadByRevisionId( PHP_INT_MAX );
		$this->assertNull( $changes );
	}

	private function assertChangesEqual( array $expected, array $changes, $start = 0 ) {
		$this->assertCount( count( $expected ), $changes );

		$i = 0;
		foreach ( $changes as $change ) {
			$expectedFields = $expected[$i]->getFields();
			$actualFields = $change->getFields();

			$this->assertGreaterThanOrEqual( $start, $actualFields['id'] );
			unset( $expectedFields['id'] );
			unset( $actualFields['id'] );

			$this->assertEquals( $expectedFields, $actualFields );
			$i++;
		}
	}

	/**
	 * We (might) already have changes in wb_changes, thus we potentially need
	 * to offset $start.
	 *
	 * @param int $start
	 * @return int
	 */
	private function offsetStart( $start ) {
		$changeIdOffset = (int)wfGetDB( DB_MASTER )->selectField(
			'wb_changes',
			'MIN( change_id )',
			// First change inserted by this test
			array( 'change_time' => '20141008161232' ),
			__METHOD__
		);

		if ( $changeIdOffset ) {
			$start = $start + $changeIdOffset - 1;
		}

		return $start;
	}

	private function getEntityChanges() {
		$changeOne = array(
			'type' => 'wikibase-item~remove',
			'time' => '20121026200049',
			'object_id' => 'q42',
			'revision_id' => '0',
			'user_id' => '0',
			'info' => '{"diff":{"type":"diff","isassoc":null,"operations":[]}}',
		);

		$changeTwo = array(
			'type' => 'wikibase-item~remove',
			'time' => '20151008161232',
			'object_id' => 'q4662',
			'revision_id' => '0',
			'user_id' => '0',
			'info' => '{"diff":{"type":"diff","isassoc":null,"operations":[]}}',
		);

		$changeThree = array(
			'type' => 'wikibase-item~remove',
			'time' => '20141008161232',
			'object_id' => 'q123',
			'revision_id' => '343',
			'user_id' => '34',
			'info' => '{"metadata":{"user_text":"BlackMagicIsEvil","bot":0,"page_id":2354,"rev_id":343,' .
				'"parent_id":897,"comment":"Fake data!"}}',
		);

		$changeOne = new EntityChange( $changeOne );
		$changeTwo = new EntityChange( $changeTwo );
		$changeThree = new EntityChange( $changeThree );

		return array( $changeOne, $changeTwo, $changeThree );
	}

}
