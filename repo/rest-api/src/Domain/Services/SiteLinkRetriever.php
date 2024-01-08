<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
interface SiteLinkRetriever {

	public function getSiteLink( ItemId $itemId, string $site ): ?SiteLink;

}
