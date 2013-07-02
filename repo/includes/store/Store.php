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
 * @todo: provide getXXX() methods for getting local pseudo-singletons (shared service objects).
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface Store {

	/**
	 * Creates a new Store instance based on the given settings.
	 * Use by StoreFactory to instantiate stores of unknown type.
	 *
	 * @param SettingsArray $settings
	 *
	 * @return Store
	 */
	public static function newFromSettings( SettingsArray $settings );

	/**
	 * Returns a new SiteLinkCache for this store.
	 *
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 *
	 * @todo: rename to newSiteLinkIndex
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
	 * Returns a TermIndex for this store.
	 *
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

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

	/**
	 * Returns an EntityLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup();

}
