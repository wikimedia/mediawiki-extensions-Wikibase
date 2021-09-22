<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\Item;

/**
 * Contains methods for looking up SiteLink conflicts
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
interface SiteLinkConflictLookup {

	/**
	 * Returns an array with the conflicts between the item and the sitelinks
	 * currently in the store. The array is empty if there are no such conflicts.
	 *
	 * The items in the return array are arrays with the following elements:
	 * - ItemId|null itemId (null if not known)
	 * - string siteId
	 * - string sitePage
	 *
	 * @param Item          $item
	 * @param int|null $db The database flag to use (optional).
	 *        Use one of DB_PRIMARY or DB_REPLICA. DB_PRIMARY can be used when you need the most recent data.
	 *
	 * @return array[] An array of arrays, each with the keys "siteId", "itemId" and "sitePage".
	 */
	public function getConflictsForItem( Item $item, int $db = null );

}
