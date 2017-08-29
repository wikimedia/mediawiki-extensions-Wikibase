<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;

// An object representing an individual API request entry (i.e. actually representing a single request
// possibly being a part of a bigger "batch" request).
interface GetEntitiesRequestElement {

	/**
	 * @return EntityId
	 */
	public function getEntityId();

}
