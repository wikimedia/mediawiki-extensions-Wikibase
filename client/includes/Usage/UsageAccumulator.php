<?php

namespace Wikibase\Usage;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Helper object for accumulating usage tracking information for a given page.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface UsageAccumulator {

	/**
	 * Registers the usage of the given aspect of the given item.
	 *
	 * @param EntityId $id
	 * @param $aspect
	 */
	public function addUsage( EntityId $id, $aspect );

	/**
	 * Returns all used EntityIds previously registered using addUsage, grouped by aspect.
	 *
	 * @return array[] An associative array mapping string keys representing aspects
	 * to arrays of EntityIds, representing the usage.
	 */
	public function getUsages();

}
 