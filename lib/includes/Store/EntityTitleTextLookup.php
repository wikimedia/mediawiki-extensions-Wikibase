<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityTitleTextLookup {

	/**
	 * Returns the title text of the entity, prefixed with the namespace, e.g. "Property:P31"
	 *
	 * @param EntityId $id
	 *
	 * @return string|null
	 */
	public function getPrefixedText( EntityId $id ): ?string;

}
