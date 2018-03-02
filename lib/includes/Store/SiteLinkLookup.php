<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Contains methods to lookup of sitelinks of lookup by sitelinks.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteLinkLookup {

	/**
	 * Returns the id of the item that is equivalent to the
	 * provided page, or null if there is none.
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle );

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
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $numericIds = [], array $siteIds = [], array $pageNames = [] );

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId );

	/**
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForSiteLink( SiteLink $siteLink );

}
