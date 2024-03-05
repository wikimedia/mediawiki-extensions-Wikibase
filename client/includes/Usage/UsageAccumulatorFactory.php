<?php

declare( strict_types=1 );

namespace Wikibase\Client\Usage;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use Wikibase\Client\ParserOutput\ParserOutputProvider;
use Wikibase\Client\ParserOutput\ParserWrappingParserOutputProvider;
use Wikibase\Client\ParserOutput\ScopedParserOutputProvider;
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

	public function newFromParserOutputProvider( ParserOutputProvider $parserOutputProvider ): UsageAccumulator {
		return new RedirectTrackingUsageAccumulator(
			new ParserOutputUsageAccumulator(
				$parserOutputProvider,
				$this->entityUsageFactory,
				$this->usageDeduplicator
			),
			$this->entityRedirectTargetLookup
		);
	}

	/**
	 * Compatibility wrapper while we migrate WikibaseLexeme to use the new ParserOutputProvider
	 * function.
	 */
	public function newFromParserOutput( ParserOutput $parserOutput ): UsageAccumulator {
		return new RedirectTrackingUsageAccumulator(
			new ParserOutputUsageAccumulator(
				new ScopedParserOutputProvider( $parserOutput ),
				$this->entityUsageFactory,
				$this->usageDeduplicator
			),
			$this->entityRedirectTargetLookup
		);
	}

	public function newFromParser( Parser $parser ): UsageAccumulator {
		return $this->newFromParserOutputProvider( new ParserWrappingParserOutputProvider( $parser ) );
	}

}
