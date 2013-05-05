<?php

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
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
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

use Wikibase\Client\WikibaseClient;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Utils;

class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @see Scribunto_LuaWikibaseLibrary::getAndRegisterEntity
	 * @todo register dependence
	 */
	protected function getAndRegisterEntity( $entityId ) {
		return WikibaseClient::getDefaultInstance()->getStore()->getEntityLookup()->getEntity(
			$entityId
		);
	}

	/**
	 * @see Scribunto_LuaWikibaseLibrary::getEntityIdForPageTitle
	 */
	protected function getEntityIdForPageTitle( $pageTitle ) {
		$globalSiteId = \Wikibase\Settings::get( 'siteGlobalID' );
		$table = WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable();
		if ( $table === null ) {
			return null;
		}

		$numericId = $table->getItemIdForLink( $globalSiteId, $pageTitle );
		if ( !is_int( $numericId ) ) {
			return null;
		}

		return new Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, $numericId );
	}

	/**
	 * @see Scribunto_LuaWikibaseLibrary::getAndRegisterEntity
	 */
    public function getGlobalSiteId() {
        return array( \Wikibase\Settings::get( 'siteGlobalID' ) );
    }
}
