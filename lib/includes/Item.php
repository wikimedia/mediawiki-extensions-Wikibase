<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * @since 0.1
 *
 * @file WikibaseItem.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Item extends Entity {

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( $siteId, $pageName, $updateType = 'add' );

	/**
	 * Removes a site link.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return boolean Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false );

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => page title (str)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getSiteLinks();

	/**
	 * Returns a deep copy of the item.
	 *
	 * @since 0.1
	 *
	 * @return Item
	 */
	public function copy();

}
