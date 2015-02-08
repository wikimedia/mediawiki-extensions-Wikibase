<?php

use ValueFormatters\FormatterOptions;
use Wikibase\DataAccess\EntityStatementsRenderer;
use Wikibase\DataAccess\PropertyIdResolver;
use Wikibase\DataAccess\SnaksFinder;
use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\PropertyLabelNotResolvedException;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class Scribunto_LuaWikibaseEntityLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLuaEntityBindings|null
	 */
	private $wbLibrary;

	private function getImplementation() {
		if ( !$this->wbLibrary ) {
			$this->wbLibrary = $this->newImplementation();
		}

		return $this->wbLibrary;
	}

	private function newImplementation() {
		// For the language we need $wgContLang, not parser target language or anything else.
		// See Scribunto_LuaLanguageLibrary::getContLangCode().
		global $wgContLang;

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$formatterOptions = new FormatterOptions( array( "language" => $wgContLang ) );

		$snakFormatter = $wikibaseClient->getSnakFormatterFactory()->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI, $formatterOptions
		);

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();

		$propertyIdResolver = new PropertyIdResolver(
			$entityLookup,
			$wikibaseClient->getStore()->getPropertyLabelResolver()
		);

		$snaksFinder = new SnaksFinder( $entityLookup );

		$entityStatementsRenderer = new EntityStatementsRenderer(
			$wgContLang,
			$propertyIdResolver,
			$snaksFinder,
			$snakFormatter,
			new ParserOutputUsageAccumulator( $this->getParser()->getOutput() )
		);

		return new WikibaseLuaEntityBindings(
			$entityStatementsRenderer,
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);
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
		return array( $this->getImplementation()->getGlobalSiteId() );
	}

	/**
	 * Render the main Snaks belonging to a Statement (which is identified by a PropertyId
	 * or the label of a Property).
	 *
	 * @since 0.5
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws ScribuntoException
	 * @return string[]
	 */
	public function formatPropertyValues( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
		$this->checkType( 'formatPropertyValues', 0, $entityId, 'string' );
		// Use 1 as index for the property id, as the first parameter comes from
		// internals of mw.wikibase.entity (an index of 2 might confuse users
		// as they only gave one parameter themselves)
		$this->checkType( 'formatPropertyValues', 1, $propertyLabelOrId, 'string' );
		$this->checkTypeOptional( 'formatPropertyValues', 2, $acceptableRanks, 'table', null );
		try {
			return array(
				$this->getImplementation()->formatPropertyValues(
					$entityId,
					$propertyLabelOrId,
					$acceptableRanks
				)
			);
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		} catch ( PropertyLabelNotResolvedException $e ) {
			return array( '' );
		}
	}

}
