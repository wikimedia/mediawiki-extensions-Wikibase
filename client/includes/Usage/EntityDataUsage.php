<?php

namespace Wikibase\Client\Usage;

use Traversable;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Covering usage of whole and part of entities
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Andrew Hall
 */
interface EntityDataUsage {

	/**
	 * Returns the entity Id for the given page
	 *
	 * @return EntityId
	 */
	public function getEntityId();

	/**
	 * Returns string containing relevant entity information
	 *
	 * @return string
	 */
	public function getIdentityString();

	/**
	 * Returns usage as array
	 *
	 * @return array
	 */
	public function asArray();

}
