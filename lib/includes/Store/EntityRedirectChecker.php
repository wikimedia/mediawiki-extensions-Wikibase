<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityRedirectChecker {

	public function isRedirect( EntityId $id ): bool;

}
