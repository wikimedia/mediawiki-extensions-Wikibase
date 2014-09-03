<?php
namespace Wikibase\Usage\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Usage\UsageTracker;
use PHPUnit_Framework_Assert as Assert;

/**
 * Base class for unit tests for UsageTracker implementation, implementing
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

	public function testUpdateUsageForPage() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q5 = new ItemId( 'Q5' );
		$q6 = new ItemId( 'Q6' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
			'all' => array( $q5 ),
		);

		$oldUsages = $this->tracker->updateUsageForPage( 23, $usages );

		Assert::assertEmpty( $oldUsages, 'No previous usages expected' );
		Assert::assertEmpty( $this->tracker->getUsageForPage( 24 ), 'No usages on other pages expected' );
		Assert::assertNotEmpty( $this->tracker->getUsageForPage( 23 ), 'Usages should have been recorded' );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3 ) ), 'Page should be known to be using item' );
		Assert::assertCount( 0, $this->tracker->getPagesUsing( array( $q6 ) ), 'Page should be known to NOT be using item' );

		$oldUsages = $this->tracker->updateUsageForPage( 23, array() );
		Assert::assertNotEmpty( $oldUsages, 'Usages befor blanking should be the original usages' );
		Assert::assertEmpty( $this->tracker->getUsageForPage( 23 ), 'No usages expected after blanking' );

		$oldUsages = $this->tracker->updateUsageForPage( 24, $usages );
		Assert::assertEmpty( $oldUsages, 'No previous usages expected' );

		Assert::assertNotEmpty( $this->tracker->getUsageForPage( 24 ), 'Usages expected' );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3 ) ), 'Page should be known to be using item' );

		$this->tracker->updateUsageForPage( 24, array() );
	}

	public function testGetUsageForPage() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

		$this->tracker->updateUsageForPage( 23, $usages );

		Assert::assertEmpty( $this->tracker->getUsageForPage( 24 ) );

		$usage = $this->tracker->getUsageForPage( 23 );
		Assert::assertCount( 2, $usage );
		Assert::assertArrayHasKey( 'label', $usage );
		Assert::assertArrayHasKey( 'sitelinks', $usage );

		Assert::assertCount( 2, $usage['label'], 'label usages' );
		Assert::assertCount( 1, $usage['sitelinks'], 'sitelink usages' );

		Assert::assertTrue( $q3->equals( reset( $usage['sitelinks'] ) ), 'sitelinks:Q3' );

		$this->tracker->updateUsageForPage( 23, array() );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

		$this->tracker->updateUsageForPage( 23, $usages );

		Assert::assertEmpty( $this->tracker->getPagesUsing( array( $q6 ) ) );

		Assert::assertEquals( array( 23 ), $this->tracker->getPagesUsing( array( $q3 ) ), 'Pages using Q3' );
		Assert::assertEquals( array( 23 ), $this->tracker->getPagesUsing( array( $q4 ) ), 'Pages using Q4' );
		Assert::assertEmpty( $this->tracker->getPagesUsing( array( $q3 ), array( 'all' ) ), 'Pages using "all" on Q3' );
		Assert::assertEmpty( $this->tracker->getPagesUsing( array( $q4 ), array( 'sitelinks' ) ), 'Pages using "sitelinks" on Q4' );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3, $q4 ), array( 'labels', 'sitelinks' ) ), 'Pages using "label" or "sitelinks" on Q3 or Q4' );

		$this->tracker->updateUsageForPage( 23, array() );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$usages = array(
			'sitelinks' => array( $q3 ),
			'label' => array( $q3, $q4 ),
		);

		$this->tracker->updateUsageForPage( 23, $usages );

		Assert::assertEmpty( $this->tracker->getUnusedEntities( array( $q4 ) ), 'Q4 should not be unused' );

		$unused = $this->tracker->getUnusedEntities( array( $q4, $q6 ) );
		Assert::assertCount( 1, $unused );
		Assert::assertEquals( $q6, reset( $unused ), 'Q6 shouold be unused' );
	}

}
