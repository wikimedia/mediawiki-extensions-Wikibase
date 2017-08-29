<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;

// An element of the response of GetEntities API respective to a single API request element.
// In other words, that would represent a response to a single API request, possibly being part of a "batch"
// request.
interface GetEntitiesResponseElement {

	/**
	 * @return EntityId
	 */
	public function getEntityId();

}
