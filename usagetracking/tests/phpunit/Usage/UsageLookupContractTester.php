<?php
namespace Wikibase\Usage\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Usage\UsageLookup;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Usage\UsageTracker;

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
	function __construct( UsageLookup $lookup, UsageTracker $tracker ) {
		$this->lookup = $lookup;
		$this->tracker = $tracker;
	}

	public function testGetUsageForPage() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

		$this->tracker->trackUsedEntities( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUsageForPage( 24 ) );

		$usage = $this->lookup->getUsageForPage( 23 );
		Assert::assertCount( 2, $usage );
		Assert::assertArrayHasKey( 'label', $usage );
		Assert::assertArrayHasKey( 'sitelinks', $usage );

		Assert::assertCount( 2, $usage['label'], 'label usages' );
		Assert::assertCount( 1, $usage['sitelinks'], 'sitelink usages' );

		Assert::assertTrue( $q3->equals( reset( $usage['sitelinks'] ) ), 'sitelinks:Q3' );

		$this->tracker->trackUsedEntities( 23, array() );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

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
			iterator_to_array( $this->lookup->getPagesUsing( array( $q3 ), array( 'all' ) ) ),
			'Pages using "all" on Q3'
		);

		Assert::assertEmpty(
			iterator_to_array( $this->lookup->getPagesUsing( array( $q4 ), array( 'sitelinks' ) ) ),
			'Pages using "sitelinks" on Q4'
		);

		Assert::assertCount( 1,
			iterator_to_array( $this->lookup->getPagesUsing( array( $q3, $q4 ), array( 'labels', 'sitelinks' ) ) ),
			'Pages using "label" or "sitelinks" on Q3 or Q4'
		);

		$this->tracker->trackUsedEntities( 23, array() );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

		$this->tracker->trackUsedEntities( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUnusedEntities( array( $q4 ) ), 'Q4 should not be unused' );

		$unused = $this->lookup->getUnusedEntities( array( $q4, $q6 ) );
		Assert::assertCount( 1, $unused );
		Assert::assertEquals( $q6, reset( $unused ), 'Q6 shouold be unused' );
	}

}
