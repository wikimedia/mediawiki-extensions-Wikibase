<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage;

use ParserOutput;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

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

	/**
	 * @var EntityRedirectTargetLookup
	 */
	private $entityRedirectTargetLookup;

	public function __construct(
		EntityUsageFactory $entityUsageFactory,
		UsageDeduplicator $usageDeduplicator,
		EntityRedirectTargetLookup $entityRedirectTargetLookup
	) {
		$this->entityUsageFactory = $entityUsageFactory;
		$this->usageDeduplicator = $usageDeduplicator;
		$this->entityRedirectTargetLookup = $entityRedirectTargetLookup;
	}

	public function newFromParserOutput( ParserOutput $parserOutput ): UsageAccumulator {
		return new RedirectTrackingUsageAccumulator(
			new ParserOutputUsageAccumulator(
				$parserOutput,
				$this->entityUsageFactory,
				$this->usageDeduplicator
			),
			$this->entityRedirectTargetLookup
		);
	}

}
