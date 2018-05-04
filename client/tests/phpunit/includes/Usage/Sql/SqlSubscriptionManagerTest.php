<?php

namespace Wikibase\Client\Tests\Usage\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\WikibaseSettings;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\Client\Usage\Sql\SqlSubscriptionManager
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlSubscriptionManagerTest extends \MediaWikiTestCase {

	protected function setUp() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'Skipping test for SqlSubscriptionManager, '
				. 'because the repo-side table wb_changes_subscription is not available.' );
		}

		$this->tablesUsed[] = 'wb_changes_subscription';

		parent::setUp();
	}

	/**
	 * @return SqlSubscriptionManager
	 */
	private function getSubscriptionManager() {
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
		$p11 = new PropertyId( 'P11' );

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

	private function fetchAllSubscriptions() {
		$db = wfGetDB( DB_MASTER );

		$res = $db->select( 'wb_changes_subscription', "*", '', __METHOD__ );

		$subscriptions = [];
		foreach ( $res as $row ) {
			$subscriptions[] = $row->cs_subscriber_id . '@' . $row->cs_entity_id;
		}

		sort( $subscriptions );
		return $subscriptions;
	}

}
