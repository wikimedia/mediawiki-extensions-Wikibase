<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
interface ItemLookup {

	/**
	 * @since 2.0
	 *
	 * @param ItemId $itemId
	 *
	 * @return Item|null
	 * @throws ItemLookupException
	 */
	public function getItemForId( ItemId $itemId );

}
