<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

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
	 * @param ItemId $item
	 * @param SiteLinkList $siteLinkList
	 * @param bool $forWrite Whether to look up conflicts in write mode (optional).
	 *        Use true when you need the most recent data, e.g. before saving the item.
	 *
	 * @return array[] An array of arrays, each with the keys "siteId", "itemId" and "sitePage".
	 */
	public function getConflictsForItem( ItemId $item, SiteLinkList $siteLinkList, bool $forWrite = false );

}
