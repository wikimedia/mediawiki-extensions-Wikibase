<?php

namespace Wikibase\Lib\Store;

use DatabaseBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Contains methods to lookup of sitelinks of lookup by sitelinks.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteLinkLookup {

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
	 * Returns the id of the item that is equivalent to the
	 * provided page, or null if there is none.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle );

	/**
	 * Returns how many links match the provided conditions.
	 *
	 * Note: this is an exact count which is expensive if the result set is big.
	 * This means you probably do not want to call this method without any conditions.
	 *
	 * @since 0.3
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return int
	 */
	public function countLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() );

	/**
	 * Returns the links that match the provided conditions.
	 * The links are returned as arrays with the following elements in specified order:
	 * - string siteId
	 * - string pageName
	 * - int itemId Numeric (unprefixed) item id
	 *
	 * Note: if the conditions are not very selective the result set can be very big.
	 * Thus the caller is responsible for not executing too expensive queries in its context.
	 *
	 * @since 0.3
	 *
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() );

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @since 0.4
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId );

	/**
	 * @since 0.4
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink );

}
