<?php

namespace Wikibase\Client\Usage\Tests;

use Wikibase\Client\Usage\HashUsageAccumulator;

/**
 * @covers Wikibase\Client\Usage\HashUsageAccumulator
 * @covers Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GNU GPL v2+
 * @author Daniel Kinzler
 */
class HashUsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$acc = new HashUsageAccumulator();
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();
	}

}
