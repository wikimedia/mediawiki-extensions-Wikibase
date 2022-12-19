<?php

namespace Wikibase\Client\Tests\Unit\Store;

use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Store\UsageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class UsageUpdaterTest extends \PHPUnit\Framework\TestCase {

	public function addUsagesForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		return [
			'empty' => [
				[],
				[],
			],

			'add usages' => [
				[ new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ],
				[ $q1, $q2 ],
			],

			'add usages, Q1 already subscribed' => [
				[ new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ],
				[ $q2 ],
			],

			'add usages, all usages already subscribed' => [
				[ new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ],
				[],
			],
		];
	}

	/**
	 * @dataProvider addUsagesForPageProvider
	 */
	public function testAddUsagesForPage( $newUsage, $unusedEntitiesToSubscribe ) {
		$usageTracker = $this->createMock( UsageTracker::class );
		$usageTracker->expects( empty( $newUsage ) ? $this->never() : $this->once() )
			->method( 'addUsedEntities' )
			->with( 23, $newUsage );

		$usageEntityIds = [];
		foreach ( $newUsage as $usage ) {
			$usageEntityIds[$usage->getEntityId()->getSerialization()] = $usage->getEntityId();
		}

		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->expects( $this->never() )
			->method( 'getUsagesForPage' );
		$usageLookup->expects( empty( $newUsage ) ? $this->never() : $this->once() )
			->method( 'getUnusedEntities' )
			->with( $usageEntityIds )
			->willReturn( $unusedEntitiesToSubscribe );

		$subscriptionManager = $this->createMock( SubscriptionManager::class );
		$subscriptionManager->expects( $this->never() )
			->method( 'unsubscribe' );

		$subscriptionManager->expects( empty( $unusedEntitiesToSubscribe ) ? $this->never() : $this->once() )
			->method( 'subscribe' )
			->with( 'testwiki', $this->callback(
				function ( $actualSubscribe ) use ( $unusedEntitiesToSubscribe ) {
					return self::arraysHaveSameContent( $actualSubscribe, $unusedEntitiesToSubscribe );
				}
			) );

		$updater = new UsageUpdater(
			'testwiki',
			$usageTracker,
			$usageLookup,
			$subscriptionManager
		);

		// assertions are done by the mock double
		$updater->addUsagesForPage( 23, $newUsage );
	}

	public function pruneUsagesForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		return [
			'empty' => [
				[],
				[],
				[],
			],

			'pruned usages' => [
				[ new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
					new EntityUsage( $q2, EntityUsage::ALL_USAGE ) ],
				[ $q1, $q2 ],
				[ $q2 ],
			],
		];
	}

	/**
	 * @dataProvider pruneUsagesForPageProvider
	 */
	public function testPruneUsagesForPage( $prunedUsages, $prunedEntityIds, $unused ) {
		$usageTracker = $this->createMock( UsageTracker::class );
		$usageTracker->expects( $this->once() )
			->method( 'pruneUsages' )
			->with( 23 )
			->willReturn( $prunedUsages );

		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->expects( $this->never() )
			->method( 'getUsagesForPage' );
		$usageLookup->expects( $this->once() )
			->method( 'getUnusedEntities' )
			->with( $this->callback(
				function( array $actualEntities ) use ( $prunedEntityIds ) {
					return self::arraysHaveSameContent( $prunedEntityIds, $actualEntities );
				}
			) )
			->willReturn( $unused );

		$subscriptionManager = $this->createMock( SubscriptionManager::class );
		$subscriptionManager->expects( $this->never() )
			->method( 'subscribe' );

		$subscriptionManager->expects( empty( $prunedUsages ) ? $this->never() : $this->once() )
			->method( 'unsubscribe' )
			->with( 'testwiki', $this->callback(
				function ( $actualUnsubscribe ) use ( $unused ) {
					return self::arraysHaveSameContent( $unused, $actualUnsubscribe );
				}
			) );

		$updater = new UsageUpdater(
			'testwiki',
			$usageTracker,
			$usageLookup,
			$subscriptionManager
		);

		// assertions are done by the mock double
		$updater->pruneUsagesForPage( 23 );
	}

	public function replaceUsagesForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$usages = [
			new EntityUsage( $q1, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q2, EntityUsage::ALL_USAGE ),
		];

		return [
			'empty' => [ [], [], [] ],

			'only new usages' => [
				[],
				$usages,
				[],
			],

			'all usages removed' => [
				$usages,
				[],
				[ $q1 ],
			],

			'new and old usages are the same' => [
				$usages,
				$usages,
				[],
			],

			'new and old usages differ (removal)' => [
				$usages,
				[ $usages[1] ],
				[ $q2 ],
			],

			'new and old usages differ (addition)' => [
				[ $usages[1] ],
				$usages,
				[],
			],

			'new and old usages differ (disjoint)' => [
				[ $usages[1] ],
				[ $usages[0] ],
				[],
			],

			'new and old usages differ (disjoint, unsubscribe)' => [
				[ $usages[1] ],
				[ $usages[0] ],
				[ $q1 ],
			],
		];
	}

	/**
	 * @dataProvider replaceUsagesForPageProvider
	 */
	public function testReplaceUsagesForPage( $oldUsage, $newUsages, $unused ) {
		$newEntityIds = $this->getEntityIds( $newUsages );

		$prunedUsages = array_diff( $oldUsage, $newUsages );
		$prunedEntityIds = $this->getEntityIds( $prunedUsages );

		$usageTracker = $this->createMock( UsageTracker::class );
		$usageTracker->expects( $this->once() )
			->method( 'replaceUsedEntities' )
			->with( 23, $newUsages )
			->willReturn( $prunedUsages );

		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->expects( $this->never() )
			->method( 'getUsagesForPage' );
		$usageLookup->expects( empty( $prunedEntityIds ) ? $this->never() : $this->once() )
			->method( 'getUnusedEntities' )
			->with( $this->callback(
				function( array $actualEntities ) use ( $prunedEntityIds ) {
					return self::arraysHaveSameContent( $prunedEntityIds, $actualEntities );
				}
			) )
			->willReturn( $unused );

		$subscriptionManager = $this->createMock( SubscriptionManager::class );
		$subscriptionManager->expects( empty( $newUsages ) ? $this->never() : $this->once() )
			->method( 'subscribe' )
			->with( 'testwiki', $newEntityIds );

		$subscriptionManager->expects( empty( $unused ) ? $this->never() : $this->once() )
			->method( 'unsubscribe' )
			->with( 'testwiki', $this->callback(
				function ( $actualUnsubscribe ) use ( $unused ) {
					return self::arraysHaveSameContent( $unused, $actualUnsubscribe );
				}
			) );

		$updater = new UsageUpdater(
			'testwiki',
			$usageTracker,
			$usageLookup,
			$subscriptionManager
		);

		// assertions are done by the mock double
		$updater->replaceUsagesForPage( 23, $newUsages );
	}

	public static function arraysHaveSameContent( $a, $b ) {
		return !count( array_diff( $a, $b ) ) && !count( array_diff( $b, $a ) );
	}

	/**
	 * @param EntityUsage[] $entityUsages
	 *
	 * @return EntityId[]
	 */
	private function getEntityIds( array $entityUsages ) {
		$entityIds = [];

		foreach ( $entityUsages as $usage ) {
			$id = $usage->getEntityId();
			$key = $id->getSerialization();

			$entityIds[$key] = $id;
		}

		return $entityIds;
	}

}
