<?php

namespace Wikibase;

/**
 * Store interface. All interaction with store Wikibase does on top
 * of storing pages and associated core MediaWiki indexing is done
 * through this interface.
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
interface Store {

	/**
	 * Returns a new SiteLinkCache for this store.
	 *
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 */
	public function newSiteLinkCache();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.1
	 */
	public function clear();

	/**
	 * Rebuilds the store.
	 *
	 * @since 0.1
	 */
	public function rebuild();

	/**
	 * Returns a new TermCache for this store.
	 *
	 * @since 0.1
	 *
	 * @return TermCache
	 */
	public function newTermCache();

	/**
	 * Returns a new IdGenerator for this store.
	 *
	 * @since 0.1
	 *
	 * @return IdGenerator
	 */
	public function newIdGenerator();

	/**
	 * Return a new EntityPerPage.
	 *
	 * @since 0.3
	 *
	 * @return EntityPerPage
	 */
	public function newEntityPerPage();

}
