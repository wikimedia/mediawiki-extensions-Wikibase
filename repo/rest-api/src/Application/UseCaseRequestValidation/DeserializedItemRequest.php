<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedItemRequest {
	public function getItem(): Item;
}
