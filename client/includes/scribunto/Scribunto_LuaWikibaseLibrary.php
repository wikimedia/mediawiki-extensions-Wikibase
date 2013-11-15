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
	 * Constructor for wrapper class, initialize member object holding implementation
	 *
	 * @since 0.5
	 *
	 */

	/* @var Scribunto_LuaWikibaseLibraryImplementation */
	protected $wbLibrary;

	public function __construct( $engine ) {
		$this->wbLibrary = new Scribunto_LuaWikibaseLibraryImplementation(
			WikibaseClient::getDefaultInstance()->getEntityIdParser(), // EntityIdParser
			WikibaseClient::getDefaultInstance()->getStore()->getEntityLookup(), // EntityLookup
			WikibaseClient::getDefaultInstance()->getEntityIdFormatter(), // EntityIdFormatter
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable() // SiteLinkLookup
		);
		parent::__construct( $engine );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.4
	 */
	public function register() {
		$lib = array( 'getEntity' => array( $this, 'getEntity' ), 'getEntityId' => array( $this, 'getEntityId' ), 'getGlobalSiteId' => array( $this, 'getGlobalSiteId' ) );
		$this->getEngine()->registerInterface( dirname( __FILE__ ) . '/mw.wikibase.lua', $lib, array() );
	}

	/**
	 * Wrapper for getEntity in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @throws ScribuntoException
	 * @return array $entityArr
	 */
	public function getEntity( $prefixedEntityId = null ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );
		try {
			$entityArr = $this->wbLibrary->getEntity( $prefixedEntityId );
			return $entityArr;
		}
		catch ( ParseException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		}
		catch ( \Exception $e ) {
			throw new ScribuntoException( 'wikibase-error-serialize-error' );
		}
	}

	/**
	 * Wrapper for getEntityId in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $pageTitle
	 *
	 * @return string $id
	 */
	public function getEntityId( $pageTitle = null ) {
		$this->checkType( 'getEntityByTitle', 1, $pageTitle, 'string' );
		return $this->wbLibrary->getEntityId( $pageTitle );
	}

	/**
	 * Wrapper for getGlobalSiteId in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 */
	public function getGlobalSiteId() {
		return $this->wbLibrary->getGlobalSiteId();
	}
}
