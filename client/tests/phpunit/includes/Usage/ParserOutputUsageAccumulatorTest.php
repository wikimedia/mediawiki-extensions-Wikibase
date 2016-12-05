<?php

namespace Wikibase\Client\Tests\Usage;

use ParserOutput;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;

/**
 * @covers Wikibase\Client\Usage\ParserOutputUsageAccumulator
 * @covers Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulatorTest extends \PHPUnit_Framework_TestCase {

	public function testAddGetUsage() {
		$parserOutput = new ParserOutput();
		$acc = new ParserOutputUsageAccumulator( $parserOutput );
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();

		$this->assertNotNull( $parserOutput->getExtensionData( 'wikibase-entity-usage' ) );
	}

}
