<?php

namespace Wikibase\Tests\Repo;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Notifications\DatabaseChangeTransmitter
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DatabaseChangeTransmitterTest extends \MediaWikiTestCase {

	public function transmitChangeProvider() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$factory = $wikibaseRepo->getEntityChangeFactory();

		$time = wfTimestamp( TS_MW );

		$change1 = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );
		$change1->setField( 'time', $time );

		$change2 = $factory->newForEntity( EntityChange::REMOVE, new ItemId( 'Q42' ) );
		$change2->setField( 'time', $time );
		$change2->setDiff( new Diff() );

		return array(
			array(
				array(
					'change_type' => 'wikibase-item~add',
					'change_time' => $time,
					'change_object_id' => 'q21389475',
					'change_revision_id' => '0',
					'change_user_id' => '0',
					'change_info' => '[]',
				),
				$change1
			),
			array(
				array(
					'change_type' => 'wikibase-item~remove',
					'change_time' => $time,
					'change_object_id' => 'q42',
					'change_revision_id' => '0',
					'change_user_id' => '0',
					'change_info' => '{"diff":{"type":"diff","isassoc":null,"operations":[]}}',
				),
				$change2
			)
		);
	}

	/**
	 * @dataProvider transmitChangeProvider
	 */
	public function testTransmitChange( array $expected, EntityChange $change ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$db = wfGetDB( DB_MASTER );
		$tableName = $wikibaseRepo->getStore()->getChangesTable()->getName();

		$db->delete( $tableName, '*', __METHOD__ );
		$this->tablesUsed[] = $tableName;

		$channel = new DatabaseChangeTransmitter();
		$channel->transmitChange( $change );

		$res = $db->select( $tableName, '*', array(), __METHOD__ );

		$this->assertEquals( 1, $res->numRows(), 'row count' );

		$row = (array)$res->current();
		$this->assertTrue( is_numeric( $row['change_id'] ) );

		unset( $row['change_id'] );
		$this->assertEquals( $expected, $row );
	}

}
