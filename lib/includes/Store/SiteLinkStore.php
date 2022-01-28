<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Contains methods for write actions on the sitelink store.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteLinkStore extends SiteLinkLookup, EntityByLinkedTitleLookup {

	/**
	 * Saves the links for the provided item.
	 *
	 * @param Item $item
	 *
	 * @return bool Success indicator
	 */
	public function saveLinksOfItem( Item $item ): bool;

	/**
	 * Removes the links for the provided item.
	 *
	 * @param ItemId $itemId
	 *
	 * @return bool Success indicator
	 */
	public function deleteLinksOfItem( ItemId $itemId ): bool;

}
