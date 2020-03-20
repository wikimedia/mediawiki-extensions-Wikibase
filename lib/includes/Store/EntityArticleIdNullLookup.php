<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * An EntityArticleIdLookup that always returns NULL. Useful in cases were entities are not associated with any local articles, e.g.
 * in the case of federated properties.
 *
 * @license GPL-2.0-or-later
 */
class EntityArticleIdNullLookup implements EntityArticleIdLookup {

	public function getArticleId( EntityId $id ): ?int {
		return null;
	}
}
