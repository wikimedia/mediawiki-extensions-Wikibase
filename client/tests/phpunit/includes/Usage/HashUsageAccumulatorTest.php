<?php

namespace Wikibase\Client\Tests\Usage;

use Wikibase\Client\Usage\HashUsageAccumulator;

/**
 * @covers Wikibase\Client\Usage\HashUsageAccumulator
 * @covers Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HashUsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$acc = new HashUsageAccumulator();
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();
	}

}
