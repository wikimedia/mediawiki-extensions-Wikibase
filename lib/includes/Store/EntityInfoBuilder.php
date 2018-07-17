<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A builder for collecting information about a batch of entities.
 *
 * @license GPL-2.0-or-later
 */
interface EntityInfoBuilder {

	/**
	 * TODO: rename to getEntityInfo
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $languageCodes
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, array $languageCodes );

}
