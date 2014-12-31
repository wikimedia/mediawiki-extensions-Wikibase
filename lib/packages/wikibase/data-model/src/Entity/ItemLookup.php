<?php

namespace Wikibase\DataModel\Entity;

/**
 * @since 2.5
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
	 * @return Item
	 * @throws ItemNotFoundException
	 */
	public function getItemForId( ItemId $itemId );

}
