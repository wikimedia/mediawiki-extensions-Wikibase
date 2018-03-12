<?php

namespace Wikibase\Client\Tests\Usage;

use ParserOutput;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\ParserOutputUsageAccumulator
 * @covers Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
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

	public function testDeduplicatorIsCalledOnce() {
		$deduplicator = $this->getMockBuilder( UsageDeduplicator::class )
			->disableOriginalConstructor()
			->getMock();
		$deduplicator->expects( $this->once() )
			->method( 'deduplicate' );

		$acc = new ParserOutputUsageAccumulator( new ParserOutput(), $deduplicator );
		$acc->addUsage( new EntityUsage( new ItemId( 'Q1' ), EntityUsage::LABEL_USAGE ) );
		$acc->getUsages();
	}

}
