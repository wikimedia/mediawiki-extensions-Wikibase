<?php

namespace Wikibase\Tests\Repo;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Notifications\DatabaseChangeNotificationChannel
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DatabaseChangeTransmitterTest extends \MediaWikiTestCase {

	public function testTransmitChange() {
		$factory = WikibaseRepo::getDefaultInstance()->getEntityChangeFactory();
		$change = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );
		$change->setField( 'time', wfTimestamp( TS_MW ) );

		$db = wfGetDB( DB_MASTER );
		$tableName = WikibaseRepo::getDefaultInstance()->getStore()->getChangesTable()->getName();

		$db->delete( $tableName, '*', __METHOD__  );
		$this->tablesUsed[] = $tableName;

		$channel = new DatabaseChangeTransmitter();
		$channel->transmitChange( $change );

		$count = $db->selectField( $tableName, 'count(*)', array(), __METHOD__ );

		$this->assertEquals( 1, intval( $count ), 'row count' );
	}

}
