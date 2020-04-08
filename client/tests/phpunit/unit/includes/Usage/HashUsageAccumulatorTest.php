<?php

namespace Wikibase\Client\Tests\Unit\Usage;

use Wikibase\Client\Tests\Mocks\Usage\UsageAccumulatorContractTester;
use Wikibase\Client\Usage\HashUsageAccumulator;

/**
 * @covers \Wikibase\Client\Usage\HashUsageAccumulator
 * @covers \Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HashUsageAccumulatorTest extends \PHPUnit\Framework\TestCase {

	public function testAddGetUsage() {
		$acc = new HashUsageAccumulator();
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();
	}

}
