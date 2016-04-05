<?php

namespace Wikibase\Client\Test\Store;

use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Store\UsageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UsageUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function addUsagesForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$t1 = '20150523054223';

		return array(
			'empty' => array(
				[],
				$t1,
				[],
			),

			'add usages' => array(
				array( new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ),
				$t1,
				array( $q1, $q2 ),
			),
		);
	}

	/**
	 * @dataProvider addUsagesForPageProvider
	 */
	public function testAddUsagesForPage( $newUsage, $touched, $subscribe ) {
		$usageTracker = $this->getMock( UsageTracker::class );
		$usageTracker->expects( $this->once() )
			->method( 'trackUsedEntities' )
			->with( 23, $newUsage, $touched );

		$usageLookup = $this->getMock( UsageLookup::class );
		$usageLookup->expects( $this->never() )
			->method( 'getUsagesForPage' );
		$usageLookup->expects( $this->never() )
			->method( 'getUnusedEntities' );

		$subscriptionManager = $this->getMock( SubscriptionManager::class );
		$subscriptionManager->expects( $this->never() )
			->method( 'unsubscribe' );

		if ( empty( $subscribe ) ) {
			// PHPUnit 3.7 doesn't like a with() assertion combined with an exactly( 0 ) assertion.
			$subscriptionManager->expects( $this->never() )
				->method( 'subscribe' );
		} else {
			$subscriptionManager->expects( $this->once() )
				->method( 'subscribe' )
				->with( 'testwiki', $this->callback(
					function ( $actualSubscribe ) use ( $subscribe ) {
						return self::arraysHaveSameContent( $actualSubscribe, $subscribe );
					}
				) );
		}

		$updater = new UsageUpdater(
			'testwiki',
			$usageTracker,
			$usageLookup,
			$subscriptionManager
		);

		// assertions are done by the mock double
		$updater->addUsagesForPage( 23, $newUsage, $touched );
	}

	public function pruneUsagesForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$t1 = '20150523054223';

		return array(
			'empty' => array(
				$t1,
				[],
				[],
				[],
			),

			'pruned usages' => array(
				$t1,
				array( new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ),
				array( $q1, $q2 ),
				array( $q2 ),
			),
		);
	}

	/**
	 * @dataProvider pruneUsagesForPageProvider
	 */
	public function testPruneUsagesForPage( $lastUpdatedBefore, $prunedUsages, $prunedEntityIds, $unused ) {
		$usageTracker = $this->getMock( UsageTracker::class );
		$usageTracker->expects( $this->once() )
			->method( 'pruneStaleUsages' )
			->with( 23, $lastUpdatedBefore )
			->will( $this->returnValue( $prunedUsages ) );

		$usageLookup = $this->getMock( UsageLookup::class );
		$usageLookup->expects( $this->never() )
			->method( 'getUsagesForPage' );
		$usageLookup->expects( $this->once() )
			->method( 'getUnusedEntities' )
			->with( $this->callback(
				function( array $actualEntities ) use ( $prunedEntityIds ) {
					return self::arraysHaveSameContent( $prunedEntityIds, $actualEntities );
				}
			) )
			->will( $this->returnValue( $unused ) );

		$subscriptionManager = $this->getMock( SubscriptionManager::class );
		$subscriptionManager->expects( $this->never() )
			->method( 'subscribe' );

		if ( empty( $prunedUsages ) ) {
			// PHPUnit 3.7 doesn't like a with() assertion combined with an exactly( 0 ) assertion.
			$subscriptionManager->expects( $this->never() )
				->method( 'unsubscribe' );
		} else {
			$subscriptionManager->expects( $this->once() )
				->method( 'unsubscribe' )
				->with( 'testwiki', $this->callback(
					function ( $actualUnsubscribe ) use ( $unused ) {
						return self::arraysHaveSameContent( $unused, $actualUnsubscribe );
					}
				) );
		}

		$updater = new UsageUpdater(
			'testwiki',
			$usageTracker,
			$usageLookup,
			$subscriptionManager
		);

		// assertions are done by the mock double
		$updater->pruneUsagesForPage( 23, $lastUpdatedBefore );
	}

	public static function arraysHaveSameContent( $a, $b ) {
		return !count( array_diff( $a, $b ) ) && !count( array_diff( $b, $a ) );
	}

}
