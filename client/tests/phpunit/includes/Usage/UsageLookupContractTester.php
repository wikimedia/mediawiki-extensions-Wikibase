<?php
namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Usage\UsageLookup;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Client\Usage\UsageTracker;

/**
 * Base class for unit tests for UsageLookup implementations, providing
 * generic tests for the interface's contract.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageLookupContractTester {

	/**
	 * @var UsageLookup
	 */
	private $lookup;

	/**
	 * @var UsageTracker
	 */
	private $tracker;

	/**
	 * @param UsageLookup $lookup The lookup under test
	 * @param UsageTracker $tracker A tracker to supply data to the lookup.
	 */
	public function __construct( UsageLookup $lookup, UsageTracker $tracker ) {
		$this->lookup = $lookup;
		$this->tracker = $tracker;
	}

	public function testGetUsageForPage() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );

		$usages = array( $u3i, $u3l, $u4l );

		$this->tracker->trackUsedEntities( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUsageForPage( 24 ) );

		$actualUsage = $this->lookup->getUsageForPage( 23 );
		Assert::assertCount( 3, $actualUsage );

		$actualUsageStrings = $this->getUsageStrings( $actualUsage );
		$expectedUsageStrings = $this->getUsageStrings( $usages );
		Assert::assertEquals( $expectedUsageStrings, $actualUsageStrings );

		$this->tracker->trackUsedEntities( 23, array() );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );

		$usages = array( $u3i, $u3l, $u4l );

		$this->tracker->trackUsedEntities( 23, $usages );

		Assert::assertEmpty(
			iterator_to_array( $this->lookup->getPagesUsing( array( $q6 ) ) )
		);

		Assert::assertEquals(
			array( 23 ),
			iterator_to_array( $this->lookup->getPagesUsing( array( $q3 ) ) ),
			'Pages using Q3'
		);

		Assert::assertEquals(
			array( 23 ),
			iterator_to_array( $this->lookup->getPagesUsing( array( $q4 ) ) ),
			'Pages using Q4'
		);

		Assert::assertEmpty(
			iterator_to_array( $this->lookup->getPagesUsing( array( $q3 ), array( EntityUsage::ALL_USAGE ) ) ),
			'Pages using "all" on Q3'
		);

		Assert::assertEmpty(
			iterator_to_array( $this->lookup->getPagesUsing( array( $q4 ), array( EntityUsage::SITELINK_USAGE ) ) ),
			'Pages using "sitelinks" on Q4'
		);

		Assert::assertCount( 1,
			iterator_to_array( $this->lookup->getPagesUsing( array( $q3, $q4 ), array( EntityUsage::LABEL_USAGE, EntityUsage::SITELINK_USAGE ) ) ),
			'Pages using "label" or "sitelinks" on Q3 or Q4'
		);

		$this->tracker->trackUsedEntities( 23, array() );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE );

		$usages = array( $u3i, $u3l, $u4l );

		$this->tracker->trackUsedEntities( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUnusedEntities( array( $q4 ) ), 'Q4 should not be unused' );

		$unused = $this->lookup->getUnusedEntities( array( $q4, $q6 ) );
		Assert::assertCount( 1, $unused );
		Assert::assertEquals( $q6, reset( $unused ), 'Q6 shouold be unused' );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	public function getUsageStrings( array $usages ) {
		$strings = array_map( function( EntityUsage $usage ) {
			return $usage->getIdentityString();
		}, $usages );

		sort( $strings );
		return $strings;
	}

}
