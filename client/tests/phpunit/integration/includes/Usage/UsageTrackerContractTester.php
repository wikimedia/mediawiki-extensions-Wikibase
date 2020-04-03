<?php

namespace Wikibase\Client\Tests\Integration\Usage;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Helper class for testing UsageTracker implementations,
 * providing generic tests for the interface's contract.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
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
	 *
	 * @throws InvalidArgumentException
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
	 * @return EntityUsage[]
	 */
	private function getUsages( $pageId ) {
		return call_user_func( $this->getUsagesCallback, $pageId );
	}

	private function getTestUsages() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usagesT1 = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' ),
		];

		$usagesT2 = [
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		];

		return [ $usagesT1, $usagesT2 ];
	}

	public function testAddUsedEntities() {
		list( $usagesT1, $usagesT2 ) = $this->getTestUsages();

		$this->tracker->addUsedEntities( 23, $usagesT1 );

		// Entries present in $usagesT1 should be set.
		$this->assertSameUsages( $usagesT1, $this->getUsages( 23 ) );

		// Also add $usagesT2 and check that both T1 and T2 are present.
		$this->tracker->addUsedEntities( 23, $usagesT2 );

		$this->assertSameUsages(
				array_unique( array_merge( $usagesT1, $usagesT2 ) ),
				$this->getUsages( 23 )
		);
	}

	public function testReplaceUsedEntities() {
		list( $usagesT1, $usagesT2 ) = $this->getTestUsages();
		$usageAll = array_unique( array_merge( $usagesT1, $usagesT2 ) );

		$this->tracker->replaceUsedEntities( 23, $usagesT1 );

		// Entries present in $usagesT1 should be set.
		$this->assertSameUsages( $usagesT1, $this->getUsages( 23 ) );

		$this->tracker->replaceUsedEntities( 23, $usagesT2 );

		// Entries present in $usagesT2 should be set.
		$this->assertSameUsages( $usagesT2, $this->getUsages( 23 ) );

		$this->tracker->replaceUsedEntities( 42, $usageAll );

		// Entries present in $usageAll should be set on page #42
		$this->assertSameUsages( $usageAll, $this->getUsages( 42 ) );

		// Entries present for page #23 stay unchanged.
		$this->assertSameUsages( $usagesT2, $this->getUsages( 23 ) );
	}

	public function testPruneUsages() {
		list( $usagesT1, ) = $this->getTestUsages();

		$this->tracker->addUsedEntities( 23, $usagesT1 );
		$this->tracker->addUsedEntities( 24, $usagesT1 );

		// Entries present in $usagesT1 should be set.
		$this->assertSameUsages( $usagesT1, $this->getUsages( 23 ) );

		// Pruning should remove all entries
		$this->tracker->pruneUsages( 23 );

		$this->assertSameUsages( [], $this->getUsages( 23 ) );

		// Usages on page #24 stay unchanged
		$this->assertSameUsages( $usagesT1, $this->getUsages( 24 ) );
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
