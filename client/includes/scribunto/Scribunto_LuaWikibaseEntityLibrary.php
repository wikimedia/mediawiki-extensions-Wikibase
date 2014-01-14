<?php

use Wikibase\Client\WikibaseClient;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Settings;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class Scribunto_LuaWikibaseEntityLibrary extends Scribunto_LuaLibraryBase {

	/* @var Wikibase\Client\WikibaseLuaEntityBindings */
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

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$formatterOptions = new FormatterOptions( array( "language" => $wgContLang ) );

		$snakFormatter = $wikibaseClient->getSnakFormatterFactory()->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI, $formatterOptions
		);

		$this->wbLibrary = new WikibaseLuaEntityBindings(
			$snakFormatter,
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getStore()->getEntityLookup(),
			$wikibaseClient->getEntityIdFormatter(),
			Settings::get( 'siteGlobalID' ),
			$wgContLang
		);

		parent::__construct( $engine );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.5
	 */
	public function register() {
		$lib = array(
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' ),
			'renderClaimsByPropertyId' => array( $this, 'renderClaimsByPropertyId' ),
		);

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.entity.lua', $lib, array()
		);
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

	/**
	 * Take a snak array from Lua and format it
	 *
	 * @since 0.5
	 *
	 * @param string $entityId
	 * @param string $propertyId
	 *
	 * @return array
	 */
	public function renderClaimsByPropertyId( $entityId, $propertyId ) {
		$this->checkType( 'renderClaimsByPropertyId', 1, $entityId, 'string' );
		$this->checkType( 'renderClaimsByPropertyId', 2, $propertyId, 'string' );
		return $this->wbLibrary->renderClaimsByPropertyId( $entityId, $propertyId );
	}

}
