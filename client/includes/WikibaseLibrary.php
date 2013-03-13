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
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */

class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.4
	 */
	public function register() {
		$lib = array( 'getEntity' => array( $this, 'getEntity' ) );
		$this->getEngine()->registerInterface( dirname( __FILE__ ) . '/../resources/' . 'mw.wikibase.lua', $lib, array() );
	}
	/**
	 * Get entity from prefixed ID (e.g. "Q23") and return it as serialized array.
	 *
	 * @since 0.4
	 *
	 * @param $prefixedEntityId
	 *
	 * @return array $entityArr
	 */
	public function getEntity( $prefixedEntityId ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );
		$entityObject = Wikibase\ClientStoreFactory::getStore()->newEntityLookup()->getEntity( Wikibase\EntityId::newFromPrefixedId( $prefixedEntityId ) );

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $entityObject );

		$opt = new \Wikibase\Lib\Serializers\EntitySerializationOptions();
		$serializer->setOptions( $opt );

		$entityArr = $serializer->getSerialized( $entityObject );
		return array( $entityArr );
	}
}
