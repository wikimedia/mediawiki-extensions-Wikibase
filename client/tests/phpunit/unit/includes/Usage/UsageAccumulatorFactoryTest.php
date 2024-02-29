<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use Wikibase\Client\ParserOutput\ScopedParserOutputProvider;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

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
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);

		$fakeParserOutput = $this->createStub( ParserOutput::class );
		$parserOutputProvider = new ScopedParserOutputProvider( $fakeParserOutput );

		$this->assertInstanceOf(
			UsageAccumulator::class,
			$factory->newFromParserOutputProvider( $parserOutputProvider )
		);
	}

	public function testGetParserOutputUsageAccumulatorForParser(): void {
		$factory = new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);

		$fakeParser = $this->createStub( Parser::class );

		$this->assertInstanceOf(
			UsageAccumulator::class,
			$factory->newFromParser( $fakeParser )
		);
	}
}
