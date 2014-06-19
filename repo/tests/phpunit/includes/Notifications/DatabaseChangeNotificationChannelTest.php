<?php

namespace Wikibase\Tests\Repo;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\Repo\Notifications\DatabaseChangeNotificationChannel;
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
class DatabaseChangeNotificationChannelTest extends \MediaWikiTestCase {

	public function testSendChangeNotification() {
		$factory = WikibaseRepo::getDefaultInstance()->getEntityChangeFactory();
		$change = $factory->newForEntity( EntityChange::ADD, new ItemId( 'Q21389475' ) );

		$db = wfGetDB( DB_MASTER );
		$tableName = WikibaseRepo::getDefaultInstance()->getStore()->getChangesTable()->getName();

		$db->delete( $tableName, '*', __METHOD__  );
		$this->tablesUsed[] = $tableName;

		$channel = new DatabaseChangeNotificationChannel();
		$channel->sendChangeNotification( $change );

		$count = $db->selectField( $tableName, 'count(*)', array(), __METHOD__ );

		$this->assertEquals( 1, intval( $count ), 'row count' );
	}

}
