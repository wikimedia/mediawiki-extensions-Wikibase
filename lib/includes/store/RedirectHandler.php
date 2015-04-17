<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for handling redirects in lookups
 */
interface RedirectHandler {
	/**
	 * Handle redirect
	 * @param EntityId $source
	 * @param EntityId $target
	 * @return EntityRevision|null
	 */
	public function handleRedirect( EntityId $source, EntityId $target );
}
