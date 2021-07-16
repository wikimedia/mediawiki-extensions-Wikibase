<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use ParserOutput;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @license GPL-2.0-or-later
 *
 * @group Wikibase
 *
 * @covers Wikibase\Client\Usage\UsageAccumulatorFactory
 */
class UsageAccumulatorFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testGetParserOutputUsageAccumulator(): void {
		$factory = new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] )
		);

		$fakeParserOutput = $this->createStub( ParserOutput::class );

		$this->assertInstanceOf( ParserOutputUsageAccumulator::class, $factory->newFromParserOutput( $fakeParserOutput ) );
	}
}
