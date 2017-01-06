<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for a factory service for EntityInfoBuilder instances.
 *
 * @see EntityInfoBuilder
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface EntityInfoBuilderFactory {

	/**
	 * Returns a new EntityInfoBuilder for gathering information about the
	 * Entities specified by the given IDs.
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $entityIds );

}
