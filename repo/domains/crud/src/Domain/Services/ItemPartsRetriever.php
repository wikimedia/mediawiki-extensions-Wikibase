<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemParts;

/**
 * @license GPL-2.0-or-later
 */
interface ItemPartsRetriever {

	public function getItemParts( ItemId $itemId, array $fields ): ?ItemParts;

}
