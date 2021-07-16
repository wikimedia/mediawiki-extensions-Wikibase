<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage;

use ParserOutput;

/**
 * @license GPL-2.0-or-later
 */
class UsageAccumulatorFactory {

	/**
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	/**
	 * @var UsageDeduplicator
	 */
	private $usageDeduplicator;

	public function __construct( EntityUsageFactory $entityUsageFactory, UsageDeduplicator $usageDeduplicator ) {
		$this->entityUsageFactory = $entityUsageFactory;
		$this->usageDeduplicator = $usageDeduplicator;
	}

	public function newFromParserOutput( ParserOutput $parserOutput ): UsageAccumulator {
		return new ParserOutputUsageAccumulator(
			$parserOutput,
			$this->entityUsageFactory,
			$this->usageDeduplicator
		);
	}

}
