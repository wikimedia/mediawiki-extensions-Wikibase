<?php
namespace Wikibase\Usage\Tests;

use ParserOutput;
use Wikibase\Usage\ParserOutputUsageAccumulator;

/**
 * @covers Wikibase\Usage\ParserOutputUsageAccumulatorTest
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$acc =  new ParserOutputUsageAccumulator( new ParserOutput() );
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();
	}

}
