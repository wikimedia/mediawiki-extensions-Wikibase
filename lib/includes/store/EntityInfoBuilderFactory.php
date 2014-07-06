<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A factory interface for EntityInfoBuilder instances.
 *
 * @see EntityInfoBuilder
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityInfoBuilderFactory {

	/**
	 * @see EntityInfoBuilderFactory::newEntityInfoBuilder
	 *
	 * @param EntityId[] $ids
	 *
	 * @return EntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $ids );
}
