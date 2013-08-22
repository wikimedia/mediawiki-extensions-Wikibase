<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\ItemId;

/**
 * Contains methods for interaction with the entity cache.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @todo: rename to SiteLinkIndex
 */
interface SiteLinkCache extends SiteLinkLookup {

	/**
	 * Saves the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item, $function = null );

	/**
	 * Removes the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param ItemId $itemId
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksOfItem( ItemId $itemId, $function = null );

	/**
	 * Clears all sitelinks from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}