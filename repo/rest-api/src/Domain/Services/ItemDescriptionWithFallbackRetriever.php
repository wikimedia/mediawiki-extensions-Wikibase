<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;

/**
 * @license GPL-2.0-or-later
 */
interface ItemDescriptionWithFallbackRetriever {

	public function getDescription( ItemId $itemId, string $languageCode ): ?Description;

}
