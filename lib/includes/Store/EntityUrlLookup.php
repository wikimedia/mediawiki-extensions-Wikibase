<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityUrlLookup {

	public function getFullUrl( EntityId $id ): ?string;

}
