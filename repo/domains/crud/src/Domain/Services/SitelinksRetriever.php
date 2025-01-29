<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;

/**
 * @license GPL-2.0-or-later
 */
interface SitelinksRetriever {

	public function getSitelinks( ItemId $itemId ): Sitelinks;

}
