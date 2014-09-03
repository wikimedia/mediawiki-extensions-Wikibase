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

		$this->tracker->updateUsageForPage( 23, $usages );

		Assert::assertEmpty( $this->tracker->getUsageForPage( 24 ) );
		Assert::assertNotEmpty( $this->tracker->getUsageForPage( 23 ) );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3 ) ) );
		Assert::assertCount( 0, $this->tracker->getPagesUsing( array( $q6 ) ) );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3, $q4, $q6 ) ) );

		$this->tracker->updateUsageForPage( 23, array() );
		$this->tracker->updateUsageForPage( 24, $usages );

		Assert::assertEmpty( $this->tracker->getUsageForPage( 23 ) );
		Assert::assertNotEmpty( $this->tracker->getUsageForPage( 24 ) );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3 ) ) );

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

		Assert::assertCount( 2, $usage['label'] );
		Assert::assertCount( 1, $usage['sitelinks'] );

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

		Assert::assertEquals( array( 23 ), $this->tracker->getPagesUsing( array( $q3 ) ) );
		Assert::assertEquals( array( 23 ), $this->tracker->getPagesUsing( array( $q4 ) ) );
		Assert::assertEmpty( $this->tracker->getPagesUsing( array( $q3 ), array( 'all' ) ) );
		Assert::assertEmpty( $this->tracker->getPagesUsing( array( $q4 ), array( 'sitelinks' ) ) );
		Assert::assertCount( 1, $this->tracker->getPagesUsing( array( $q3, $q4 ), array( 'labels', 'sitelinks' ) ) );

		$this->tracker->updateUsageForPage( 23, array() );
	}

}
