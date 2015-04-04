<?php

namespace Wikibase\Lib\Store;

use DatabaseBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Contains methods for interaction with the entity cache.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteLinkCache extends SiteLinkLookup {

	/**
	 * Returns an array with the conflicts between the item and the sitelinks
	 * currently in the store. The array is empty if there are no such conflicts.
	 *
	 * The items in the return array are arrays with the following elements:
	 * - int itemId Numeric (unprefixed) item id
	 * - string siteId
	 * - string sitePage
	 *
	 * @since 0.1
	 *
	 * @param Item          $item
	 * @param DatabaseBase|null $db The database object to use (optional).
	 *        If conflict checking is performed as part of a save operation,
	 *        this should be used to provide the master DB connection that will
	 *        also be used for saving. This will preserve transactional integrity
	 *        and avoid race conditions.
	 *
	 * @return array[]
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null );

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
