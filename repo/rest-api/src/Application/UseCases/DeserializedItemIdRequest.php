<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedItemIdRequest {
	public function getItemId(): ItemId;
}
