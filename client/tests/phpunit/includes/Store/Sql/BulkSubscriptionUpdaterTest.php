<?php

namespace Wikibase\Client\Tests\Store\Sql;

use PHPUnit_Framework_MockObject_Matcher_Invocation;
use Wikibase\Client\Store\Sql\BulkSubscriptionUpdater;
use Wikibase\WikibaseSettings;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;

/**
 * @covers Wikibase\Client\Store\Sql\BulkSubscriptionUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class BulkSubscriptionUpdaterTest extends \MediaWikiTestCase {

	protected function setUp() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes_subscription table." );
		}

		$this->tablesUsed[] = 'wb_changes_subscription';
		$this->tablesUsed[] = EntityUsageTable::DEFAULT_TABLE_NAME;

		parent::setUp();
	}

	/**
	 * @param int $batchSize
	 *
	 * @return BulkSubscriptionUpdater
	 */
	private function getBulkSubscriptionUpdater( $batchSize = 10 ) {
		$loadBalancer = wfGetLB();

		return new BulkSubscriptionUpdater(
			new SessionConsistentConnectionManager( $loadBalancer, false ),
			new SessionConsistentConnectionManager( $loadBalancer, false ),
			'testwiki',
			false,
			$batchSize
		);
	}

	public function testPurgeSubscriptions() {
		$this->truncateEntityUsage();
		$this->truncateSubscriptions();
		$this->putSubscriptions( [
			[ 'P11', 'dewiki' ],
			[ 'Q11', 'dewiki' ],
			[ 'Q22', 'dewiki' ],
			[ 'Q22', 'frwiki' ],
			[ 'P11', 'testwiki' ],
			[ 'Q11', 'testwiki' ],
			[ 'Q22', 'testwiki' ],
		] );

		$updater = $this->getBulkSubscriptionUpdater( 2 );
		$updater->setProgressReporter( $this->getMessageReporter( $this->exactly( 2 ) ) );
		$updater->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$updater->purgeSubscriptions();

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@P11',
			'dewiki@Q11',
			'dewiki@Q22',
			'frwiki@Q22',
		];

		$this->assertEquals( $expected, $actual );
	}

	public function testPurgeSubscriptions_startItem() {
		$this->truncateEntityUsage();
		$this->putSubscriptions( [
			[ 'P11', 'dewiki' ],
			[ 'Q11', 'dewiki' ],
			[ 'Q22', 'dewiki' ],
			[ 'Q22', 'frwiki' ],
			[ 'P11', 'testwiki' ],
			[ 'Q11', 'testwiki' ],
			[ 'Q22', 'testwiki' ],
		] );

		$updater = $this->getBulkSubscriptionUpdater( 2 );
		$updater->setProgressReporter( $this->getMessageReporter( $this->exactly( 1 ) ) );
		$updater->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$updater->purgeSubscriptions( new ItemId( 'Q20' ) );

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@P11',
			'dewiki@Q11',
			'dewiki@Q22',
			'frwiki@Q22',
			'testwiki@P11',
			'testwiki@Q11',
		];

		$this->assertEquals( $expected, $actual );
	}

	public function testUpdateSubscriptions() {
		$this->truncateEntityUsage();
		$this->putSubscriptions( [
			[ 'P11', 'dewiki' ],
			[ 'Q11', 'dewiki' ],
			[ 'Q22', 'dewiki' ],
			[ 'Q22', 'frwiki' ],
		] );
		$this->putEntityUsage( [
			[ 'P11', 11 ],
			[ 'Q11', 11 ],
			[ 'Q22', 22 ],
			[ 'Q22', 33 ],
		] );

		$updater = $this->getBulkSubscriptionUpdater( 2 );
		$updater->setProgressReporter( $this->getMessageReporter( $this->exactly( 2 ) ) );
		$updater->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$updater->updateSubscriptions();

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@P11',
			'dewiki@Q11',
			'dewiki@Q22',
			'frwiki@Q22',
			'testwiki@P11',
			'testwiki@Q11',
			'testwiki@Q22',
		];

		$this->assertEquals( $expected, $actual );
	}

	public function testUpdateSubscriptions_startItem() {
		$this->truncateEntityUsage();
		$this->putSubscriptions( [
			[ 'P11', 'dewiki' ],
			[ 'Q11', 'dewiki' ],
			[ 'Q22', 'dewiki' ],
			[ 'Q22', 'frwiki' ],
		] );
		$this->putEntityUsage( [
			[ 'P11', 11 ],
			[ 'Q11', 11 ],
			[ 'Q22', 22 ],
			[ 'Q22', 33 ],
		] );

		$updater = $this->getBulkSubscriptionUpdater( 2 );
		$updater->setProgressReporter( $this->getMessageReporter( $this->exactly( 1 ) ) );
		$updater->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$updater->updateSubscriptions( new ItemId( 'Q20' ) );

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@P11',
			'dewiki@Q11',
			'dewiki@Q22',
			'frwiki@Q22',
			'testwiki@Q22',
		];

		$this->assertEquals( $expected, $actual );
	}

	private function truncateEntityUsage() {
		$db = wfGetDB( DB_MASTER );
		$db->delete( EntityUsageTable::DEFAULT_TABLE_NAME, '*' );
	}

	private function putEntityUsage( array $entries ) {
		$db = wfGetDB( DB_MASTER );

		$db->startAtomic( __METHOD__ );

		foreach ( $entries as $entry ) {
			list( $entityId, $pageId ) = $entry;
			$aspect = 'X';

			$db->insert( EntityUsageTable::DEFAULT_TABLE_NAME, [
				'eu_entity_id' => $entityId,
				'eu_aspect' => $aspect,
				'eu_page_id' => (int)$pageId,
			], __METHOD__ );
		}

		$db->endAtomic( __METHOD__ );
	}

	private function truncateSubscriptions() {
		$db = wfGetDB( DB_MASTER );
		$db->delete( 'wb_changes_subscription', '*' );
	}

	private function putSubscriptions( array $entries ) {
		$db = wfGetDB( DB_MASTER );

		$db->startAtomic( __METHOD__ );

		foreach ( $entries as $entry ) {
			list( $entityId, $subscriberId ) = $entry;

			$db->insert( 'wb_changes_subscription', [
				'cs_entity_id' => $entityId,
				'cs_subscriber_id' => $subscriberId,
			], __METHOD__ );
		}

		$db->endAtomic( __METHOD__ );
	}

	private function fetchAllSubscriptions() {
		$db = wfGetDB( DB_MASTER );

		$res = $db->select( 'wb_changes_subscription', "*", '', __METHOD__ );

		$subscriptions = [];
		foreach ( $res as $row ) {
			$subscriptions[] = $row->cs_subscriber_id . '@' . $row->cs_entity_id;
		}

		return $subscriptions;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
	 *
	 * @return ExceptionHandler
	 */
	private function getExceptionHandler( PHPUnit_Framework_MockObject_Matcher_Invocation $matcher ) {
		$mock = $this->getMock( ExceptionHandler::class );
		$mock->expects( $matcher )
			->method( 'handleException' );

		return $mock;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
	 *
	 * @return MessageReporter
	 */
	private function getMessageReporter( PHPUnit_Framework_MockObject_Matcher_Invocation $matcher ) {
		$mock = $this->getMock( MessageReporter::class );
		$mock->expects( $matcher )
			->method( 'reportMessage' );

		return $mock;
	}

}
