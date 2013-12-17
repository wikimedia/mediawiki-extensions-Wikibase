<?php

use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Settings;
use ValueParsers\ParseException;
use Wikibase\Client\WikibaseClient;
use Wikibase\Utils;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */

class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/* @var Scribunto_LuaWikibaseLibraryImplementation */
	protected $wbLibrary;

	/**
	 * Constructor for wrapper class, initialize member object holding implementation
	 *
	 * @param Scribunto_LuaEngine $engine
	 * @since 0.5
	 */
	public function __construct( $engine ) {
		// For the language we need $wgContLang, not parser target language or anything else.
		// See Scribunto_LuaLanguageLibrary::getContLangCode().
		global $wgContLang;
		$language = $wgContLang;
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$this->wbLibrary = new Scribunto_LuaWikibaseLibraryImplementation(
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getStore()->getEntityLookup(),
			$wikibaseClient->getEntityIdFormatter(),
			$wikibaseClient->getStore()->getSiteLinkTable(),
			$wikibaseClient->getLanguageFallbackChainFactory(),
			$language,
			Utils::getLanguageCodes(),
			Settings::get( 'siteGlobalID' )
		);

		parent::__construct( $engine );
	}

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

		return $this->getEngine()->registerInterface(
			dirname( __FILE__ ) . '/mw.wikibase.lua', $lib, array()
		);
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
		catch ( EntityIdParsingException $e ) {
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
