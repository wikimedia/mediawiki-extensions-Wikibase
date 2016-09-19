<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Store\Sql\SqlSubscriptionLookup;

/**
 * @covers Wikibase\Store\Sql\SqlSubscriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookupTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes_subscription';
	}

	private function insertSubscriptions( array $rows ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert( 'wb_changes_subscription', $rows, __METHOD__ );
	}

	public function testGetSubscriptions() {
		$subscriptions = array(
			array( 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'P1' ),
			array( 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q3' ),
			array( 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q7' ),
			array( 'cs_subscriber_id' => 'dewiki', 'cs_entity_id' => 'Q2' ),
		);

		$this->insertSubscriptions( $subscriptions );

		$lookup = new SqlSubscriptionLookup( wfGetLB() );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', array(
			new PropertyId( 'P1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q7' ),
			new PropertyId( 'P3' ),
		) );

		$actual = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $subscriptions );

		sort( $actual );

		$expected = array( 'P1', 'Q7' );

		$subscribers = $lookup->getSubscribers( new ItemId( 'Q2' ) );
		$expectedSubscibers = [ 'dewiki' ];

		$this->assertEquals( $expected, $actual );
		$this->assertEquals( $expectedSubscibers, $subscribers );
	}

}
