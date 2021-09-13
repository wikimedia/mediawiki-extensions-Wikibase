<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlSubscriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlSubscriptionLookupTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes_subscription';
	}

	private function insertSubscriptions( array $rows ) {
		$this->db->insert( 'wb_changes_subscription', $rows, __METHOD__ );
	}

	public function testGetSubscriptions() {
		$subscriptions = [
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'P1' ],
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q3' ],
			[ 'cs_subscriber_id' => 'enwiki', 'cs_entity_id' => 'Q7' ],
			[ 'cs_subscriber_id' => 'dewiki', 'cs_entity_id' => 'Q2' ],
		];

		$this->insertSubscriptions( $subscriptions );

		$lookup = new SqlSubscriptionLookup( WikibaseRepo::getRepoDomainDbFactory()->newRepoDb() );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', [
			new NumericPropertyId( 'P1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q7' ),
			new NumericPropertyId( 'P3' ),
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

		$lookup = new SqlSubscriptionLookup( WikibaseRepo::getRepoDomainDbFactory()->newRepoDb() );

		$subscribers = $lookup->getSubscribers( new ItemId( 'Q2' ) );
		$expected = [ 'dewiki', 'enwiki' ];

		sort( $subscribers );

		$this->assertEquals( $expected, $subscribers );

		$noSubscribers = $lookup->getSubscribers( new ItemId( 'Q7' ) );

		$this->assertEquals( [], $noSubscribers );
	}

}
