<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\Item;
use Wikimedia\Rdbms\IDatabase;

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
	 * - ItemId itemId
	 * - string siteId
	 * - string sitePage
	 *
	 * @param Item          $item
	 * @param IDatabase|null $db The database object to use (optional).
	 *        If conflict checking is performed as part of a save operation,
	 *        this should be used to provide the master DB connection that will
	 *        also be used for saving. This will preserve transactional integrity
	 *        and avoid race conditions.
	 *
	 * @return array[] An array of arrays, each with the keys "siteId", "itemId" and "sitePage".
	 */
	public function getConflictsForItem( Item $item, IDatabase $db = null );

}
