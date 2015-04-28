<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use PHPUnit_Framework_MockObject_Matcher_Invocation;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Repo\Store\Sql\ChangesSubscriptionTableBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\Sql\ChangesSubscriptionTableBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ChangesSubscriptionTableBuilderTest extends \MediaWikiTestCase {

	const TABLE_NAME = 'wb_changes_subscription';

	public function setUp() {
		$mode = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'subscriptionLookupMode' );

		if ( $mode !== 'subscriptions' && $mode !== 'subscriptions+sitelinks' ) {
			$this->markTestSkipped( 'Skipping test for ChangesSubscriptionTableBuilder, '
				. 'because usage of the wb_changes_subscription table is disabled.' );
		}

		$this->tablesUsed[] = self::TABLE_NAME;
		$this->tablesUsed[] = 'wb_items_per_site';

		parent::setUp();
	}

	/**
	 * @param int $batchSize
	 *
	 * @return ChangesSubscriptionTableBuilder
	 */
	private function getChangesSubscriptionTableBuilder( $batchSize, $verbose ) {
		$loadBalancer = wfGetLB();

		return new ChangesSubscriptionTableBuilder( $loadBalancer, self::TABLE_NAME, $batchSize, $verbose );
	}

	public function testFillSubscriptionTable() {
		$this->truncateItemPerSite();
		$this->putItemPerSite( array(
			array( 11, 'dewiki' ),
			array( 11, 'enwiki' ),
			array( 22, 'dewiki' ),
			array( 22, 'frwiki' ),
		) );

		$primer = $this->getChangesSubscriptionTableBuilder( 3, false );
		$primer->setProgressReporter( $this->getMessageReporter( $this->exactly( 2 ) ) );
		$primer->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$primer->fillSubscriptionTable();

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = array(
			'dewiki@Q11',
			'dewiki@Q22',
			'enwiki@Q11',
			'frwiki@Q22',
		);

		$this->assertEquals( $expected, $actual );
	}

	public function testFillSubscriptionTable_startItem() {
		$this->truncateItemPerSite();
		$this->putItemPerSite( array(
			array( 11, 'dewiki' ),
			array( 11, 'enwiki' ),
			array( 22, 'dewiki' ),
			array( 22, 'frwiki' ),
		) );

		$primer = $this->getChangesSubscriptionTableBuilder( 3, true );
		$primer->setProgressReporter( $this->getMessageReporter( $this->exactly( 4 ) ) );
		$primer->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$primer->fillSubscriptionTable( new ItemId( 'Q20' ) );

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = array(
			'dewiki@Q22',
			'frwiki@Q22',
		);

		$this->assertEquals( $expected, $actual );
	}

	private function truncateItemPerSite() {
		$db = wfGetDB( DB_MASTER );
		$db->delete( 'wb_items_per_site', '*' );
	}

	private function putItemPerSite( array $entries ) {
		$db = wfGetDB( DB_MASTER );

		$db->startAtomic( __METHOD__ );

		foreach ( $entries as $entry ) {
			list( $itemId, $siteId ) = $entry;
			$db->insert( 'wb_items_per_site', array(
				'ips_item_id' => (int)$itemId,
				'ips_site_id' => $siteId,
				'ips_site_page' => 'Page_about_Q' . $itemId. '_on_' . $siteId,
			), __METHOD__ );
		}

		$db->endAtomic( __METHOD__ );
	}

	private function fetchAllSubscriptions() {
		$db = wfGetDB( DB_MASTER );

		$res = $db->select( self::TABLE_NAME, "*", '', __METHOD__ );

		$subscriptions = array();
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
		$mock = $this->getMock( 'Wikibase\Lib\Reporting\ExceptionHandler' );
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
		$mock = $this->getMock( 'Wikibase\Lib\Reporting\MessageReporter' );
		$mock->expects( $matcher )
			->method( 'reportMessage' );

		return $mock;
	}

}
