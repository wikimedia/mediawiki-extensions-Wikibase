<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelRetriever {

	public function getLabel( ItemId $itemId, string $languageCode ): ?Label;

}
