<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
interface ItemLookup {

	/**
	 * Returns the Item of which the id is given.
	 *
	 * @param ItemId $itemId
	 *
	 * @return Item|null
	 * @throws ItemLookupException
	 */
	public function getItemForId( ItemId $itemId );

}
