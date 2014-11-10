<?php

use Wikibase\Client\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Utils;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLuaBindings
	 */
	private $wbLibrary;

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

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$labelLookup = new LanguageLabelLookup(
			new EntityRetrievingTermLookup( $entityLookup ),
			$wgContLang->getCode()
		);

		$this->wbLibrary = new WikibaseLuaBindings(
			$wikibaseClient->getEntityIdParser(),
			$entityLookup,
			$wikibaseClient->getStore()->getSiteLinkTable(),
			$wikibaseClient->getLanguageFallbackChainFactory(),
			$language,
			$wikibaseClient->getSettings(),
			$wikibaseClient->getPropertyDataTypeLookup(),
			$labelLookup,
			new ParserOutputUsageAccumulator( $this->getEngine()->getParser()->getOutput() ),
			Utils::getLanguageCodes(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);

		parent::__construct( $engine );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function register() {
		$lib = array(
			'getLabel' => array( $this, 'getLabel' ),
			'getEntity' => array( $this, 'getEntity' ),
			'getSetting' => array( $this, 'getSetting' ),
			'getEntityId' => array( $this, 'getEntityId' ),
			'getSiteLink' => array( $this, 'getSiteLink' ),
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' )
		);

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lua', $lib, array()
		);
	}

	/**
	 * Wrapper for getEntity in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 * @param bool $legacyStyle Whether to return a legacy style entity
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	public function getEntity( $prefixedEntityId, $legacyStyle ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );
		$this->checkType( 'getEntity', 2, $legacyStyle, 'boolean' );
		try {
			$entityArr = $this->wbLibrary->getEntity( $prefixedEntityId, $legacyStyle );
			return array( $entityArr );
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
	 * @return array
	 */
	public function getEntityId( $pageTitle = null ) {
		$this->checkType( 'getEntityByTitle', 1, $pageTitle, 'string' );
		return array( $this->wbLibrary->getEntityId( $pageTitle ) );
	}

	/**
	 * Wrapper for getGlobalSiteId in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @return string[]
	 */
	public function getGlobalSiteId() {
		return array( $this->wbLibrary->getGlobalSiteId() );
	}

	/**
	 * Wrapper for getSetting in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $setting
	 *
	 * @return array
	 */
	public function getSetting( $setting ) {
		$this->checkType( 'setting', 1, $setting, 'string' );
		return array( $this->wbLibrary->getSetting( $setting ) );
	}

	/**
	 * Wrapper for getLabel in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array
	 */
	public function getLabel( $prefixedEntityId ) {
		$this->checkType( 'getLabel', 1, $prefixedEntityId, 'string' );
		return array( $this->wbLibrary->getLabel( $prefixedEntityId ) );
	}

	/**
	 * Wrapper for getSiteLink in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array
	 */
	public function getSiteLink( $prefixedEntityId ) {
		$this->checkType( 'getSiteLink', 1, $prefixedEntityId, 'string' );
		return array( $this->wbLibrary->getSiteLink( $prefixedEntityId ) );
	}
}
