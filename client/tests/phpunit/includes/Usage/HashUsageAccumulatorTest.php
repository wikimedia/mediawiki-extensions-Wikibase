<?php
namespace Wikibase\Usage\Tests;

use Wikibase\Usage\HashUsageAccumulator;

/**
 * @covers Wikibase\Usage\HashUsageAccumulatorTest
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class HashUsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$acc =  new HashUsageAccumulator();
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();
	}

}
