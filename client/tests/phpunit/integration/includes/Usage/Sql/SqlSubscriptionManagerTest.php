<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Usage\Sql;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * @covers \Wikibase\Client\Usage\Sql\SqlSubscriptionManager
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlSubscriptionManagerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'Skipping test for SqlSubscriptionManager, '
				. 'because the repo-side table wb_changes_subscription is not available.' );
		}

		$this->tablesUsed[] = 'wb_changes_subscription';

		parent::setUp();
	}

	private function getSubscriptionManager(): SqlSubscriptionManager {
		return new SqlSubscriptionManager(
			new SessionConsistentConnectionManager(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			)
		);
	}

	public function testSubscribeUnsubscribe() {
		$manager = $this->getSubscriptionManager();

		$q11 = new ItemId( 'Q11' );
		$q22 = new ItemId( 'Q22' );
		$p11 = new NumericPropertyId( 'P11' );

		$manager->subscribe( 'enwiki', [ $q11, $p11 ] );
		$manager->subscribe( 'dewiki', [ $q22 ] );
		$manager->subscribe( 'dewiki', [ $q22, $q11 ] );

		$this->assertEquals(
			[
				'dewiki@Q11',
				'dewiki@Q22',
				'enwiki@P11',
				'enwiki@Q11',
			],
			$this->fetchAllSubscriptions()
		);

		$manager->unsubscribe( 'enwiki', [ $q11, $q22 ] );
		$manager->unsubscribe( 'dewiki', [ $q22 ] );
		$manager->unsubscribe( 'dewiki', [] );

		$this->assertEquals(
			[
				'dewiki@Q11',
				'enwiki@P11',
			],
			$this->fetchAllSubscriptions()
		);
	}

	private function fetchAllSubscriptions(): array {
		$res = $this->db->select( 'wb_changes_subscription', "*", '', __METHOD__ );

		$subscriptions = [];
		foreach ( $res as $row ) {
			$subscriptions[] = $row->cs_subscriber_id . '@' . $row->cs_entity_id;
		}

		sort( $subscriptions );
		return $subscriptions;
	}

}
