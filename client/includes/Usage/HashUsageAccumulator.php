<?php

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
	private $usages = [];

	/**
	 * @see UsageAccumulator::addUsage
	 *
	 * @param EntityUsage $usage
	 */
	public function addUsage( EntityUsage $usage ) {
		$key = $usage->getIdentityString();
		$this->usages[$key] = $usage;
	}

	/**
	 * @see UsageAccumulator::getUsage
	 *
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		return $this->usages;
	}

}
