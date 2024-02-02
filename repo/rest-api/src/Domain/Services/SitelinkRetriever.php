<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;

/**
 * @license GPL-2.0-or-later
 */
interface SitelinkRetriever {

	public function getSitelink( ItemId $itemId, string $siteId ): ?Sitelink;

}
