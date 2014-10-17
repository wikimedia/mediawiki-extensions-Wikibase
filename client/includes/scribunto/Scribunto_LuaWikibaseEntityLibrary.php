<?php

use ValueFormatters\FormatterOptions;
use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SnakFormatter;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class Scribunto_LuaWikibaseEntityLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLuaEntityBindings
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

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$formatterOptions = new FormatterOptions( array( "language" => $wgContLang ) );

		$snakFormatter = $wikibaseClient->getSnakFormatterFactory()->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI, $formatterOptions
		);

		$this->wbLibrary = new WikibaseLuaEntityBindings(
			$snakFormatter,
			$wikibaseClient->getStore()->getEntityLookup(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			$wgContLang
		);

		parent::__construct( $engine );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function register() {
		$lib = array(
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' ),
			'formatPropertyValues' => array( $this, 'formatPropertyValues' ),
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
	 * @return string[]
	 */
	public function getGlobalSiteId() {
		return array( $this->wbLibrary->getGlobalSiteId() );
	}

	/**
	 * Render the main Snaks belonging to a Claim (which is identified by a PropertyId).
	 *
	 * @since 0.5
	 *
	 * @param string $entityId
	 * @param string $propertyId
	 * @param int[] $acceptableRanks
	 *
	 * @throws ScribuntoException
	 * @return string[]
	 */
	public function formatPropertyValues( $entityId, $propertyId, array $acceptableRanks ) {
		$this->checkType( 'formatPropertyValues', 0, $entityId, 'string' );
		// Use 1 as index for the property id, as the first parameter comes from
		// internals of mw.wikibase.entity (an index of 2 might confuse users
		// as they only gave one parameter themselves)
		$this->checkType( 'formatPropertyValues', 1, $propertyId, 'string' );
		$this->checkType( 'formatPropertyValues', 2, $acceptableRanks, 'table' );
		try {
			return array( $this->wbLibrary->formatPropertyValues( $entityId, $propertyId, $acceptableRanks ) );
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		}
	}

}
