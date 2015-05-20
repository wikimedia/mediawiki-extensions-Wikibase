<?php

namespace Wikibase\Client\Tests\Usage;

use InvalidArgumentException;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Helper class for testing UsageTracker implementations,
 * providing generic tests for the interface's contract.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTrackerContractTester {

	/**
	 * @var UsageTracker
	 */
	private $tracker;

	/**
	 * @var callable function( $pageId, $timestamp ) returns EntityUsage[]
	 */
	private $getUsagesCallback;

	/**
	 * @param UsageTracker $tracker
	 * @param callable $getUsagesCallback function( $pageId, $timestamp ) returns EntityUsage[]
	 */
	public function __construct( UsageTracker $tracker, $getUsagesCallback ) {
		if ( !is_callable( $getUsagesCallback ) ) {
			throw new InvalidArgumentException( '$getUsagesCallback must be callable' );
		}

		$this->tracker = $tracker;
		$this->getUsagesCallback = $getUsagesCallback;
	}

	/**
	 * @param int $pageId
	 *
	 * @param $timestamp
	 *
	 * @return EntityUsage[]
	 */
	private function getUsages( $pageId, $timestamp ) {
		return call_user_func( $this->getUsagesCallback, $pageId, $timestamp );
	}

	public function testTrackUsedEntities() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$this->tracker->trackUsedEntities( 23, $usagesT1, $t1 );
		$this->tracker->trackUsedEntities( 23, $usagesT2, $t2 );

		// Track again for a different page ID, with swapped timestamps, to detect leakage.
		$this->tracker->trackUsedEntities( 24, $usagesT2, $t1 );
		$this->tracker->trackUsedEntities( 24, $usagesT1, $t2 );

		// Entries present in $usagesT1 and $usagesT2 should have been touched with $t2.
		$updatedUsages = $this->getUsages( 23, $t2 );
		$this->assertSameUsages( $usagesT2, $updatedUsages );

		// Note: Entries in $usagesT1 but not in $usagesT2 may or may not be still present
		// with the stale $t1 timestamp. trackUsedEntities() only guarantees that
		// the provided usage entries will be tracked, and are updated to the new timestamp.
	}

	public function testRemoveEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usages = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$entitiesToRemove = array( $q3, $q5 );
		$expectedUsage = array(
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$this->tracker->trackUsedEntities( 23, $usages, '20150102030405' );
		$this->tracker->removeEntities( $entitiesToRemove );

		$oldUsages = $this->getUsages( 23, '20150102030405' );
		$this->assertSameUsages( $expectedUsage, $oldUsages );

		$this->tracker->trackUsedEntities( 24, array(), '20150102030405' );
	}

	public function testPruneStaleUsages() {
		$t1 = '20150111000000';
		$t2 = '20150222000000';

		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
		);

		$usagesT2 = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		// init database: $usagesT2 get timestamp $t2,
		// array_diff( $usagesT1, $usagesT2 ) get timestamp $t1.
		$this->tracker->trackUsedEntities( 23, $usagesT1, $t1 );
		$this->tracker->trackUsedEntities( 23, $usagesT2, $t2 );

		// pruning should remove entries with a timestamp < $t2
		$this->tracker->pruneStaleUsages( 23, $t2 );

		$actualUsages = $this->getUsages( 23, $t2 );
		$this->assertSameUsages( $usagesT2, $actualUsages );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param EntityUsage[] $actual
	 * @param string $message
	 */
	public function assertSameUsages( array $expected, array $actual, $message = '' ) {
		$expected = $this->getUsageStrings( $expected );
		$actual = $this->getUsageStrings( $actual );

		sort( $expected );
		sort( $actual );

		Assert::assertEquals( $expected, $actual, $message );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	public function getUsageStrings( array $usages ) {
		return array_values(
			array_map( function( EntityUsage $usage ) {
				return $usage->getIdentityString();
			}, $usages )
		);
	}

}
