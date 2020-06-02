<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A builder for collecting information about a batch of entities.
 *
 * @deprecated Any usage of EntityInfo is now unintended and should be removed T254283
 *
 * @license GPL-2.0-or-later
 */
interface EntityInfoBuilder {

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 *
	 * @deprecated Any usage of EntityInfo is now unintended and should be removed T254283
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes );

}
