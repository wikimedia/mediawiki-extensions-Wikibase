<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;

/**
 * @license GPL-2.0-or-later
 */
interface ItemDescriptionsRetriever {

	public function getDescriptions( ItemId $itemId ): ?Descriptions;

}
