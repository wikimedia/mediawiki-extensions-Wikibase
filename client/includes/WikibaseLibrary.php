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

use ValueParsers\ParseException;
use Wikibase\Client\WikibaseClient;

class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.4
	 */
	public function register() {
		$lib = array(
			'getEntity' => array( $this, 'getEntity' ),
			'getEntityId' => array( $this, 'getEntityId' ),
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' )
		);
		$this->getEngine()->registerInterface( dirname( __FILE__ ) . '/../resources/' . 'mw.wikibase.lua', $lib, array() );
	}
	/**
	 * Get entity from prefixed ID (e.g. "Q23") and return it as serialized array.
	 *
	 * @since 0.4
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array $entityArr
	 */
	public function getEntity( $prefixedEntityId = null ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityIdParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		try {
			$entityId = $entityIdParser->parse( $prefixedEntityId );
		}
		catch ( ParseException $parseException ) {
			throw $this->getEngine()->newException( 'wikibase-error-invalid-entity-id' );
		}

		$entityObject = WikibaseClient::getDefaultInstance()->getStore()->getEntityLookup()->getEntity(
			$entityId
		);

		if ( $entityObject == null ) {
			return array( null );
		}

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $entityObject );

		$opt = new \Wikibase\Lib\Serializers\EntitySerializationOptions();
		$serializer->setOptions( $opt );

		try {
			$entityArr = $serializer->getSerialized( $entityObject );
			return array( $entityArr );
		} catch ( \Exception $e ) {
			throw $this->getEngine()->newException( 'wikibase-error-deserialize-error' );
		}
	}

	/**
	 * Get entity id from page title.
	 *
	 * @since 0.4
	 *
	 * @param string $pageTitle
	 *
	 * @return string $id
	 */
	public function getEntityId( $pageTitle = null ) {
		$this->checkType( 'getEntityByTitle', 1, $pageTitle, 'string' );
		$globalSiteId = \Wikibase\Settings::get( 'siteGlobalID' );
		$table = WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable();
		if ( $table == null ) {
			return array( null );
		}

		$numericId = $table->getItemIdForLink( $globalSiteId, $pageTitle );
		if ( !is_int( $numericId ) ) {
			return array( null );
		}

		$id = new Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, $numericId );
		if ( $id == null ) {
			return array( null );
		}

		return array( $id->getPrefixedId() );
	}
    /**
     * Get global site ID (e.g. "enwiki")
     * This is basically a helper function.
     * I can see this becoming part of mw.site in the Scribunto extension.
     *
     * @since 0.4
     *
     */
    public function getGlobalSiteId() {
        return array( \Wikibase\Settings::get( 'siteGlobalID' ) );
    }
}
