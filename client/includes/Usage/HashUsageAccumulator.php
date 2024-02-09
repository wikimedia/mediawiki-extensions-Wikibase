<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

/**
 * This implementation of the UsageAccumulator interface simply wraps
 * an array containing the usage information.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HashUsageAccumulator extends UsageAccumulator {

	/**
	 * @var EntityUsage[]
	 */
	private array $usages = [];

	/**
	 * @see UsageAccumulator::addUsage
	 */
	public function addUsage( EntityUsage $usage ): void {
		$key = $usage->getIdentityString();
		$this->usages[$key] = $usage;
	}

	/**
	 * @see UsageAccumulator::getUsage
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages(): array {
		return $this->usages;
	}
}
