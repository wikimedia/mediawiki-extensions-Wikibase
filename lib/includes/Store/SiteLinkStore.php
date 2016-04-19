<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Contains methods for write actions on the sitelink store.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteLinkStore extends SiteLinkLookup {

	/**
	 * Saves the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item );

	/**
	 * Removes the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param ItemId $itemId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksOfItem( ItemId $itemId );

	/**
	 * Clears all sitelinks from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
