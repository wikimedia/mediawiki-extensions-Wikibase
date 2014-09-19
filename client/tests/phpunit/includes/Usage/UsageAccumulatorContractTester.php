<?php
namespace Wikibase\Usage\Tests;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Usage\UsageAccumulator;
use PHPUnit_Framework_Assert as Assert;

/**
 * Contract tester for implementations of the UsageAccumulator interface
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageAccumulatorContractTester  {

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param UsageAccumulator $usageAccumulator
	 */
	public function __construct( UsageAccumulator $usageAccumulator ) {
		$this->usageAccumulator = $usageAccumulator;
	}

	public function testAddGetUsage() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		$this->usageAccumulator->addUsage( $q1, 'links' );
		$this->usageAccumulator->addUsage( $q2, 'label' );
		$this->usageAccumulator->addUsage( $q3, 'label' );

		$usage = $this->usageAccumulator->getUsages();
		$expected = array(
			'links' => array( $q1 ),
			'label' => array( $q2, $q3 ),
		);

		Assert::assertEquals( $expected, $usage );
	}

}
