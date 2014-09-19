<?php

namespace Wikibase\Usage;

use Wikibase\DataModel\Entity\EntityId;

/**
 * This implementation of the UsageAccumulator interface simply wraps
 * an array containing the usage information.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class HashUsageAccumulator implements UsageAccumulator {

	/**
	 * @var array[]
	 */
	private $usages;

	public function __construct( array $usages = array() ) {
		$this->usages = $usages;
	}

	/**
	 * @see UsageAccumulator::addUsage()
	 *
	 * @param EntityId $id
	 * @param $aspect
	 */
	public function addUsage( EntityId $id, $aspect ) {
		$this->usages[$aspect][] = $id;
	}

	/**
	 * @see UsageAccumulator::getUsage()
	 *
	 * @return array[]
	 */
	public function getUsages() {
		return $this->usages;
	}

}
 