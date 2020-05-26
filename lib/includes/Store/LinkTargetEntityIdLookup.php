<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Linker\LinkTarget;
use Wikibase\DataModel\Entity\EntityId;

/**
 * This lookup should be able to deal with LinkTargets that:
 *  - Link directly to an entity page (example: Property:P123 or Item:Q3)
 *  - Link to an entity page via Special:EntityPage/<EntityId> (example: Special:EntityPage/P123)
 *
 * @license GPL-2.0-or-later
 */
interface LinkTargetEntityIdLookup {

	/**
	 * Returns the EntityId for a given LinkTarget
	 *
	 * @param LinkTarget $linkTarget either directly to the Entity page, or to Special:EntityPage/<ID>
	 *
	 * @return EntityId|null
	 */
	public function getEntityId( LinkTarget $linkTarget ): ?EntityId;

}
