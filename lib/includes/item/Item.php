<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Item extends Entity {

	// TODO: remove prefix, consider client cache stuff
	const ENTITY_TYPE = 'wikibase-item';

	/**
	 * Adds a site link.
	 *
	 * @since 0.1
	 *
	 * @param SiteLink $link the link to the target page
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( SiteLink $link, $updateType = 'add' );

	/**
	 * Removes a site link.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return bool Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false );

	/**
	 * Returns the site links in an associative array with the following format:
	 * site id (str) => SiteLink
	 *
	 * @since 0.1
	 *
	 * @return array an array of SiteLink objects
	 */
	public function getSiteLinks();

	/**
	 * Returns the site link for the given site id, or null.
	 *
	 * @since 0.1
	 *
	 * @param String $siteId the id of the site to which to get the lin
	 *
	 * @return SiteLink|null the corresponding SiteLink object, or null
	 */
	public function getSiteLink( $siteId );

}
