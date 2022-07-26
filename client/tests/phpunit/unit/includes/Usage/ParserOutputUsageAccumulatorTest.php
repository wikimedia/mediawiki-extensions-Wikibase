<?php

namespace Wikibase\Client\Tests\Unit\Usage;

use ParserOutput;
use Wikibase\Client\Tests\Mocks\Usage\UsageAccumulatorContractTester;
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

	private function newEntityUsageFactory(): EntityUsageFactory {
		return new EntityUsageFactory( new BasicEntityIdParser() );
	}

	public function testAddGetUsage() {
		$parserOutput = new ParserOutput();
		$acc = new ParserOutputUsageAccumulator(
			$parserOutput,
			$this->newEntityUsageFactory(),
			new UsageDeduplicator( [] )
		);
		$tester = new UsageAccumulatorContractTester( $acc );

		$tester->testAddGetUsage();

		$this->assertNotNull( $parserOutput->getExtensionData( ParserOutputUsageAccumulator::EXTENSION_DATA_KEY ) );
	}

	public function testDeduplicatorIsCalledOnce() {
		$deduplicator = $this->createMock( UsageDeduplicator::class );
		$deduplicator->expects( $this->once() )
			->method( 'deduplicate' )
			->with( $this->containsOnlyInstancesOf( EntityUsage::class ) )
			->willReturn( '<DEDUPLICATED>' );

		$id = new ItemId( 'Q1' );
		$acc = new ParserOutputUsageAccumulator(
			new ParserOutput(),
			$this->newEntityUsageFactory(),
			$deduplicator
		);
		$acc->addUsage( new EntityUsage( $id, EntityUsage::LABEL_USAGE ) );
		$acc->addUsage( new EntityUsage( $id, EntityUsage::DESCRIPTION_USAGE ) );
		$this->assertSame( '<DEDUPLICATED>', $acc->getUsages() );
	}

}
