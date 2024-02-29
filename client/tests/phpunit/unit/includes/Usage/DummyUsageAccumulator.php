<?php

declare ( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;

/**
 * @license GPL-2.0-or-later
 */
class DummyUsageAccumulator extends UsageAccumulator {

	private HashUsageAccumulator $hashUsageAccumulator;

	public function __construct() {
		$this->hashUsageAccumulator = new HashUsageAccumulator();
	}

	public function addUsage( EntityUsage $usage ): void {
		$this->hashUsageAccumulator->addUsage( $usage );
	}

	public function getUsages(): array {
		return $this->hashUsageAccumulator->getUsages();
	}
}
