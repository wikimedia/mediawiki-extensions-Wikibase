<?php
namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
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

	function __construct( UsageTracker $tracker ) {
		$this->tracker = $tracker;
	}

	public function testTrackUsedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
			'all' => array( $q5 ),
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
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
			'all' => array( $q5 ),
		);

		$entitiesToRemove = array( $q3, $q5 );
		$expectedUsage = array(
			'label' => array( $q4 ),
		);

		$this->tracker->trackUsedEntities( 23, $usages );
		$this->tracker->removeEntities( $entitiesToRemove );

		// (ab)use trackUsedEntities() to read the current usage back.
		$oldUsages = $this->tracker->trackUsedEntities( 23, array() );

		$this->assertSameUsages( $expectedUsage, $oldUsages );

		$this->tracker->trackUsedEntities( 24, array() );
	}

	private function assertSameUsages( $expected, $actual, $message = '' ) {
		$expected = $this->getUsageStrings( $expected );
		$actual = $this->getUsageStrings( $actual );

		Assert::assertEquals( $expected, $actual, $message );
	}

	private function getUsageStrings( $usages ) {
		$strings = array();

		foreach ( $usages as $aspect => $ids ) {
			$strings = array_merge(
				$strings,
				$this->getIdStrings( $aspect, $ids )
			);
		}

		sort( $strings );
		return $strings;
	}

	private function getIdStrings( $prefix, $ids ) {
		$strings = array_map( function ( EntityId $id ) use ( $prefix ) {
			return $prefix . ':' . $id->getSerialization();
		}, $ids );

		sort( $strings );
		return $strings;
	}

}
