<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Linker\LinkTarget;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface LinkTargetEntityIdLookup {

	/**
	 * Returns the EntityId for a given LinkTarget
	 *
	 * @param LinkTarget $linkTarget
	 *
	 * @return EntityId|null
	 */
	public function getEntityId( LinkTarget $linkTarget ): ?EntityId;

}
