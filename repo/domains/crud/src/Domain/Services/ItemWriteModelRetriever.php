<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface ItemWriteModelRetriever {

	public function getItemWriteModel( ItemId $itemId ): ?Item;

}
