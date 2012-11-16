<?php

namespace Wikibase;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactory_multi.
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
 * */
class DirectSqlStore implements ClientStore {

	/**
	 * @see Store::singleton
	 *
	 * @since 0.1
	 *
	 * @return Store
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$repoWiki = Settings::get( 'repoDatabase' ); //FIXME: use the same setting for wb_changes

			$instance = new static( $repoWiki );
		}

		return $instance;
	}

	/**
	 * @var String|bool $repoWiki
	 */
	protected $repoWiki;

	public function __construct( $repoWiki ) {
		$this->repoWiki = $repoWiki;
	}

	/**
	 * @see Store::newSiteLinkTable
	 *
	 * @since 0.1
	 *
	 * @return SiteLinkLookup
	 */
	public function newSiteLinkTable() {
		return new SiteLinkRemoteTable( 'wb_items_per_site', $this->repoWiki );
	}

	/**
	 * @see Store::newEntityLookup
	 *
	 * @since 0.1
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup() {
		return new WikiPageEntityLookup( $this->repoWiki ); // entities are stored in wiki pages
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.2
	 */
	public function clear() {
		// noop
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.2
	 */
	public function rebuild() {
		$this->clear();
	}

}
