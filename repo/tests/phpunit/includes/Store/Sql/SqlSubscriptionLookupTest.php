<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
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
 * @group Database
 *
 * @license GPL-2.0-or-later
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
		$subscriptions = [
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'P1' ],
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q3' ],
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q7' ],
			[ 'cs_subscriber_id' => 'dewiki', 'cs_entity_id' => 'Q2' ],
		];

		$this->insertSubscriptions( $subscriptions );

		$lookup = new SqlSubscriptionLookup( MediaWikiServices::getInstance()->getDBLoadBalancer() );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', [
			new PropertyId( 'P1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q7' ),
			new PropertyId( 'P3' ),
		] );

		$actual = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $subscriptions );

		sort( $actual );

		$expected = [ 'P1', 'Q7' ];

		$this->assertEquals( $expected, $actual );
	}

	public function testGetSubscribers() {
		$subscriptions = [
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q2' ],
			[ 'cs_subscriber_id' => 'dewiki', 'cs_entity_id' => 'Q3' ],
			[ 'cs_subscriber_id' => 'frwiki', 'cs_entity_id' => 'Q3' ],
			[ 'cs_subscriber_id' => 'dewiki', 'cs_entity_id' => 'Q2' ],
		];

		$this->insertSubscriptions( $subscriptions );

		$lookup = new SqlSubscriptionLookup( MediaWikiServices::getInstance()->getDBLoadBalancer() );

		$subscribers = $lookup->getSubscribers( new ItemId( 'Q2' ) );
		$expected = [ 'dewiki', 'enwiki' ];

		sort( $subscribers );

		$this->assertEquals( $expected, $subscribers );

		$noSubscribers = $lookup->getSubscribers( new ItemId( 'Q7' ) );

		$this->assertEquals( [], $noSubscribers );
	}

}
