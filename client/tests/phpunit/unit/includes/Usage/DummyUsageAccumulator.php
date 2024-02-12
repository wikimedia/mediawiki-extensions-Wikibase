<?php

declare ( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use ParserOutput;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\ParserUsageAccumulator;

/**
 * @license GPL-2.0-or-later
 */
class DummyUsageAccumulator extends ParserUsageAccumulator {

	private HashUsageAccumulator $hashUsageAccumulator;

	public function __construct( ParserOutput $parserOutput ) {
		parent::__construct( $parserOutput );
		$this->hashUsageAccumulator = new HashUsageAccumulator();
	}

	public function addUsage( EntityUsage $usage ): void {
		$this->hashUsageAccumulator->addUsage( $usage );
	}

	public function getUsages(): array {
		return $this->hashUsageAccumulator->getUsages();
	}
}
