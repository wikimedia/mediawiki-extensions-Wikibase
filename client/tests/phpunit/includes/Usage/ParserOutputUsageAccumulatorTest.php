<?php

namespace Wikibase\Client\Usage\Tests;

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
 * @license GNU GPL v2+
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
