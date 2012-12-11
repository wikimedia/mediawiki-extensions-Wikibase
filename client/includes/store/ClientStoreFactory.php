<?php

namespace Wikibase;

/**
 * Factory for obtaining a client store instance.
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
 */
class ClientStoreFactory {

	/**
	 * Returns an instance of the default store, or an alternate store
	 * if so specified with the $store argument.
	 *
	 * @since 0.1
	 *
	 * @param boolean $store
	 *
	 * @return ClientStore
	 */
	public static function getStore( $store = false ) {
		global $wgWBClientStores;
		$store = ( $store === false || !array_key_exists( $store, $wgWBClientStores ) ) ? Settings::get( 'defaultClientStore' ) : $store;

		if ( !$store ) {
			if ( Settings::get( 'repoDatabase' ) ) {
				$store = 'DirectSqlStore';
			} else {
				$store = 'CachingSqlStore';
			}
		}

		$class = $wgWBClientStores[$store];

		if ( Settings::get( 'repoDatabase' ) ) {
			//FIXME: use the same setting for wb_changes
			$instance = new $class( Settings::get( 'repoDatabase' ) );
		}
		else {
			$instance = new $class;
		}

		assert( $instance instanceof ClientStore );
		return $instance;
	}

}
