<?php

declare( strict_types = 1 );

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
	 */
	public function getItemIdForLink( string $globalSiteId, string $pageTitle ): ?ItemId;

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
	 * @param int[]|null $numericIds Numeric (unprefixed) item ids, or null for no item filtering
	 * @param string[]|null $siteIds Site IDs, or null for no site filtering
	 * @param string[]|null $pageNames Page names, or null for no page filtering
	 *
	 * @return array[]
	 */
	public function getLinks(
		?array $numericIds = null,
		?array $siteIds = null,
		?array $pageNames = null
	): array;

	/**
	 * Returns an array of SiteLink objects for an item. If the item isn't known or not an Item,
	 * an empty array is returned.
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ): array;

	public function getItemIdForSiteLink( SiteLink $siteLink ): ?ItemId;

}
