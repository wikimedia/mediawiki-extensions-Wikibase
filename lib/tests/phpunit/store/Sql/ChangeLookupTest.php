<?php

namespace Wikibase\Test;

use Wikibase\EntityChange;
use Wikibase\Lib\Store\ChangeLookup;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;

/**
 * @covers Wikibase\Lib\Store\ChangeLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch
 */
class ChangeLookupTest extends \MediaWikiTestCase {

	public function testGetRecordId() {
		$change = $this->getMock( 'Wikibase\Change' );
		$change->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 42 ) );

		$changeLookup = new ChangeLookup( array(), 'doesntmatterwiki' );

		$this->assertSame( 42, $changeLookup->getRecordId( $change ) );
	}

	public function loadChunkProvider() {
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

		$changeOne = new EntityChange( null, $changeOne, false );
		$changeTwo = new EntityChange( null, $changeTwo, false );
		$changeThree = new EntityChange( null, $changeThree, false );

		return array(
			array(
				array( $changeOne ),
				array( $changeThree, $changeTwo, $changeOne ),
				3,
				1
			),
			array(
				array( $changeOne, $changeTwo ),
				array( $changeTwo, $changeTwo, $changeTwo ),
				3,
				2
			),
			array(
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

		$databaseChangeTransmitter = new DatabaseChangeTransmitter( wfGetLB() );
		foreach ( $changesToStore as $change ) {
			$databaseChangeTransmitter->transmitChange( $change );
		}

		$lookup = new ChangeLookup(
			array( 'wikibase-item~remove' => 'Wikibase\EntityChange' ),
			wfWikiID()
		);

		$changes = $lookup->loadChunk( $start, $size );

		$this->assertCount( count( $expected ), $changes );

		$i = 0;
		foreach ( $changes as $change ) {
			$expectedFields = $expected[$i]->getFields();
			$actualFields = $change->getFields();

			// We don't care for the exact id
			$this->assertGreaterThan( 0, $actualFields['id'] );
			unset( $expectedFields['id'] );
			unset( $actualFields['id'] );

			$this->assertEquals( $expectedFields, $actualFields );
			$i++;
		}
	}

}
