<?php

namespace Wikibase;

use Language;

/**
 * Client store interface.
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
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface ClientStore {

	/**
	 * Creates a new ClientStore instance based on the given settings.
	 * Use by StoreFactory to instantiate stores of unknown type.
	 *
	 * @param SettingsArray $settings
	 * @param Language      $wikiLanguage
	 *
	 * @return ClientStore
	 */
	public static function newFromSettings( SettingsArray $settings, Language $wikiLanguage );

	/**
	 * Returns a SiteLinkLookup for this store.
	 *
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable();

	/**
	 * Returns a EntityUsageIndex for this store.
	 *
	 * @since 0.4
	 *
	 * @return EntityUsageIndex
	 */
	public function getEntityUsageIndex();

	/**
	 * Returns a new EntityLookup for this store.
	 *
	 * @since 0.1
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup();

	/**
	 * Returns a PropertyLabelResolver
	 *
	 * @since 0.4
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver();

	/**
	 * Returns a TermIndex
	 *
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * Returns a new ChangesTable for this store.
	 *
	 * @since 0.4
	 *
	 * @return ChangesTable
	 *
	 * @throws \MWException if no changes table can be supplied.
	 */
	public function newChangesTable();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.2
	 */
	public function clear();

	/**
	 * Rebuilds all data in the store.
	 *
	 * @since 0.2
	 */
	public function rebuild();
}
