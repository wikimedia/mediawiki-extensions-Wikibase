<?php
namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Usage\UsageTracker;
use PHPUnit_Framework_Assert as Assert;

/**
 * Base class for unit tests for UsageTracker implementations, providing
 * generic tests for the interface's contract.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTrackerContractTester {

	/**
	 * @var UsageTracker
	 */
	private $tracker;

	public function __construct( UsageTracker $tracker ) {
		$this->tracker = $tracker;
	}

	public function testTrackUsedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usages = array(
			new EntityUsage( $q3, EntityUsage::SITELINK_USAGE ),
			new EntityUsage( $q3, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q4, EntityUsage::LABEL_USAGE ),
			new EntityUsage( $q5, EntityUsage::ALL_USAGE ),
		);

		$oldUsages = $this->tracker->trackUsedEntities( 23, $usages );
		Assert::assertEmpty( $oldUsages, 'No previous usages expected' );

		$oldUsages = $this->tracker->trackUsedEntities( 23, array() );

		$this->assertSameUsages( $usages, $oldUsages );

		$this->tracker->trackUsedEntities( 24, array() );
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

		$this->tracker->trackUsedEntities( 23, $usages );
		$this->tracker->removeEntities( $entitiesToRemove );

		// (ab)use trackUsedEntities() to read the current usage back.
		$oldUsages = $this->tracker->trackUsedEntities( 23, array() );

		$this->assertSameUsages( $expectedUsage, $oldUsages );

		$this->tracker->trackUsedEntities( 24, array() );
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
