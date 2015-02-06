<?php

use Wikibase\Client\Scribunto\EntityAccessor;
use Wikibase\Client\Scribunto\WikibaseLuaBindings;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;
use Wikibase\Utils;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLuaBindings|null
	 */
	private $luaBindings;

	/**
	 * @var EntityAccessor
	 */
	private $entityAccessor;

	private function getLuaBindings() {
		if ( !$this->luaBindings ) {
			$this->luaBindings = $this->newLuaBindings();
		}

		return $this->luaBindings;
	}

	private function getEntityAccessor() {
		if ( !$this->entityAccessor ) {
			$this->entityAccessor = $this->newEntityAccessor();
		}

		return $this->entityAccessor;
	}

	private function getLanguage() {
		// For the language we need $wgContLang, not parser target language or anything else.
		// See Scribunto_LuaLanguageLibrary::getContLangCode().
		global $wgContLang;
		return $wgContLang;
	}

	private function newEntityAccessor() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();

		return new EntityAccessor(
			$wikibaseClient->getEntityIdParser(),
			$entityLookup,
			new ParserOutputUsageAccumulator( $this->getParser()->getOutput() ),
			$wikibaseClient->getLanguageFallbackChainFactory(),
			Utils::getLanguageCodes(),
			$this->getLanguage(),
			$wikibaseClient->getPropertyDataTypeLookup()
		);
	}

	private function newLuaBindings() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();

		$fallbackChain = $wikibaseClient->getLanguageFallbackChainFactory()->newFromLanguage(
			$this->getLanguage(),
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		$labelLookup = new LanguageFallbackLabelLookup(
			new EntityRetrievingTermLookup( $entityLookup ),
			$fallbackChain
		);

		return new WikibaseLuaBindings(
			$wikibaseClient->getEntityIdParser(),
			$entityLookup,
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$wikibaseClient->getSettings(),
			$labelLookup,
			new ParserOutputUsageAccumulator( $this->getParser()->getOutput() ),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);
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
			'getSiteLinkPageName' => array( $this, 'getSiteLinkPageName' ),
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
			$entityArr = $this->getEntityAccessor()->getEntity( $prefixedEntityId, $legacyStyle );
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
		return array( $this->getLuaBindings()->getEntityId( $pageTitle ) );
	}

	/**
	 * Wrapper for getGlobalSiteId in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @return string[]
	 */
	public function getGlobalSiteId() {
		return array( $this->getLuaBindings()->getGlobalSiteId() );
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
		return array( $this->getLuaBindings()->getSetting( $setting ) );
	}

	/**
	 * Wrapper for getLabel in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return string[]
	 */
	public function getLabel( $prefixedEntityId ) {
		$this->checkType( 'getLabel', 1, $prefixedEntityId, 'string' );
		return array( $this->getLuaBindings()->getLabel( $prefixedEntityId ) );
	}

	/**
	 * Wrapper for getSiteLinkPageName in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return string[]
	 */
	public function getSiteLinkPageName( $prefixedEntityId ) {
		$this->checkType( 'getSiteLinkPageName', 1, $prefixedEntityId, 'string' );
		return array( $this->getLuaBindings()->getSiteLinkPageName( $prefixedEntityId ) );
	}
}
