<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
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
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangesSubscriptionTableBuilderTest extends \MediaWikiTestCase {

	const TABLE_NAME = 'wb_changes_subscription';

	protected function setUp() {
		$this->tablesUsed[] = self::TABLE_NAME;
		$this->tablesUsed[] = 'wb_items_per_site';

		parent::setUp();
	}

	/**
	 * @param int $batchSize
	 * @param string $verbosity
	 *
	 * @return ChangesSubscriptionTableBuilder
	 */
	private function getChangesSubscriptionTableBuilder( $batchSize, $verbosity ) {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();

		return new ChangesSubscriptionTableBuilder(
			$loadBalancer,
			WikibaseRepo::getDefaultInstance()->getEntityIdComposer(),
			self::TABLE_NAME,
			$batchSize,
			$verbosity
		);
	}

	public function testFillSubscriptionTable() {
		$this->truncateItemPerSite();
		$this->putItemPerSite( [
			[ 11, 'dewiki' ],
			[ 11, 'enwiki' ],
			[ 22, 'dewiki' ],
			[ 22, 'frwiki' ],
		] );

		$primer = $this->getChangesSubscriptionTableBuilder( 3, 'standard' );
		$primer->setProgressReporter( $this->getMessageReporter( $this->exactly( 2 ) ) );
		$primer->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$primer->fillSubscriptionTable();

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@Q11',
			'dewiki@Q22',
			'enwiki@Q11',
			'frwiki@Q22',
		];

		$this->assertEquals( $expected, $actual );
	}

	public function testFillSubscriptionTable_startItem() {
		$this->truncateItemPerSite();
		$this->putItemPerSite( [
			[ 11, 'dewiki' ],
			[ 11, 'enwiki' ],
			[ 22, 'dewiki' ],
			[ 22, 'frwiki' ],
		] );

		$primer = $this->getChangesSubscriptionTableBuilder( 3, 'verbose' );
		$primer->setProgressReporter( $this->getMessageReporter( $this->exactly( 4 ) ) );
		$primer->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 0 ) ) );

		$primer->fillSubscriptionTable( new ItemId( 'Q20' ) );

		$actual = $this->fetchAllSubscriptions();
		sort( $actual );

		$expected = [
			'dewiki@Q22',
			'frwiki@Q22',
		];

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
			$db->insert( 'wb_items_per_site', [
				'ips_item_id' => (int)$itemId,
				'ips_site_id' => $siteId,
				'ips_site_page' => 'Page_about_Q' . $itemId. '_on_' . $siteId,
			], __METHOD__ );
		}

		$db->endAtomic( __METHOD__ );
	}

	private function fetchAllSubscriptions() {
		$db = wfGetDB( DB_MASTER );

		$res = $db->select( self::TABLE_NAME, "*", '', __METHOD__ );

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
	private function getExceptionHandler( $matcher ) {
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
	private function getMessageReporter( $matcher ) {
		$mock = $this->getMock( MessageReporter::class );
		$mock->expects( $matcher )
			->method( 'reportMessage' );

		return $mock;
	}

}
