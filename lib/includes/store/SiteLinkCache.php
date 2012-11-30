<?php

namespace Wikibase;

/**
 * Contains methods for interaction with the entity cache.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * @param EntityId $itemId
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksOfItem( EntityId $itemId, $function = null );

	/**
	 * Clears all sitelinks from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}