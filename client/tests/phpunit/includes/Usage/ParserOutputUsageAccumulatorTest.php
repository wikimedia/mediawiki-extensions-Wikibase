<?php

namespace Wikibase\Client\Tests\Usage;

use ParserOutput;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Usage\ParserOutputUsageAccumulator
 * @covers \Wikibase\Client\Usage\UsageAccumulator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ParserOutputUsageAccumulatorTest extends \PHPUnit\Framework\TestCase {

	public function testAddGetUsage() {
		$parserOutput = new ParserOutput();
		$acc = new ParserOutputUsageAccumulator(
			$parserOutput,
			new EntityUsageFactory( new BasicEntityIdParser() )
		);
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();

		$this->assertNotNull( $parserOutput->getExtensionData( 'wikibase-entity-usage' ) );
	}

	public function testAddGetUsageLegacyDataAndConstruction() {
		$parserOutput = new ParserOutput();
		$acc = new ParserOutputUsageAccumulator( $parserOutput );

		$entityUsage = new EntityUsage( new ItemId( 'Q5' ), EntityUsage::LABEL_USAGE );
		$parserOutput->setExtensionData( 'wikibase-entity-usage', [ $entityUsage ] );

		$this->assertSame(
			[ $entityUsage->getIdentityString() => $entityUsage ],
			$acc->getUsages()
		);
	}

	public function testDeduplicatorIsCalledOnce() {
		$deduplicator = $this->getMockBuilder( UsageDeduplicator::class )
			->disableOriginalConstructor()
			->getMock();
		$deduplicator->expects( $this->once() )
			->method( 'deduplicate' )
			->with( $this->containsOnlyInstancesOf( EntityUsage::class ) )
			->willReturn( '<DEDUPLICATED>' );

		$id = new ItemId( 'Q1' );
		$acc = new ParserOutputUsageAccumulator(
			new ParserOutput(),
			new EntityUsageFactory( new BasicEntityIdParser() ),
			$deduplicator
		);
		$acc->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE ) );
		$acc->addUsage( new EntityUsage( $id, EntityUsage::DESCRIPTION_USAGE ) );
		$this->assertSame( '<DEDUPLICATED>', $acc->getUsages() );
	}

}
