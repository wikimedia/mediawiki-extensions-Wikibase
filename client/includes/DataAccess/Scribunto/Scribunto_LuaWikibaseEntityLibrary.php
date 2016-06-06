<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Language;
use Scribunto_LuaLibraryBase;
use ScribuntoException;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
		$lang = $this->getLanguage();

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$languageFallbackChain = $wikibaseClient->getDataAccessLanguageFallbackChain( $lang );

		$formatterOptions = new FormatterOptions( array(
			SnakFormatter::OPT_LANG => $lang->getCode(),
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallbackChain
		) );

		$snakFormatter = new UsageTrackingSnakFormatter(
			$wikibaseClient->getSnakFormatterFactory()->getSnakFormatter(
				SnakFormatter::FORMAT_WIKI,
				$formatterOptions
			),
			$this->getUsageAccumulator(),
			$languageFallbackChain->getFetchLanguageCodes()
		);

		$entityLookup = $wikibaseClient->getRestrictedEntityLookup();

		$propertyIdResolver = new PropertyIdResolver(
			$entityLookup,
			$wikibaseClient->getStore()->getPropertyLabelResolver()
		);

		$entityStatementsRenderer = new StatementTransclusionInteractor(
			$lang,
			$propertyIdResolver,
			new SnaksFinder(),
			$snakFormatter,
			$entityLookup
		);

		return new WikibaseLuaEntityBindings(
			$entityStatementsRenderer,
			$wikibaseClient->getEntityIdParser(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * Returns the language to use. If we are on a multilingual wiki
	 * (allowDataAccessInUserLanguage is true) this will be the user's interface
	 * language, otherwise it will be the content language.
	 * In a perfect world, this would equal Parser::getTargetLanguage.
	 *
	 * This doesn't split the ParserCache by language yet, please see
	 * self::splitParserCacheIfMultilingual for that.
	 *
	 * This can probably be removed after T114640 has been implemented.
	 *
	 * @return Language
	 */
	private function getLanguage() {
		global $wgContLang;

		if ( $this->allowDataAccessInUserLanguage() ) {
			// Can't use ParserOptions::getUserLang as that already splits the ParserCache
			$userLang = $this->getParserOptions()->getUser()->getOption( 'language' );

			return Language::factory( $userLang );
		}

		return $wgContLang;
	}

	/**
	 * Splits the page's ParserCache in case we're on a multilingual wiki
	 */
	private function splitParserCacheIfMultilingual() {
		if ( $this->allowDataAccessInUserLanguage() ) {
			// ParserOptions::getUserLang splits the ParserCache
			$this->getParserOptions()->getUserLang();
		}
	}

	/**
	 * @return bool
	 */
	private function allowDataAccessInUserLanguage() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		return $settings->getSetting( 'allowDataAccessInUserLanguage' );
	}

	/**
	 * @return ParserOutputUsageAccumulator
	 */
	public function getUsageAccumulator() {
		return new ParserOutputUsageAccumulator( $this->getParser()->getOutput() );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = array(
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' ),
			'formatPropertyValues' => array( $this, 'formatPropertyValues' ),
		);

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.entity.lua', $lib, array()
		);
	}

	/**
	 * Wrapper for getGlobalSiteId in WikibaseLuaEntityBindings
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
	 * @return string[]|null[]
	 */
	public function formatPropertyValues( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
		$this->checkType( 'formatPropertyValues', 0, $entityId, 'string' );
		// Use 1 as index for the property id, as the first parameter comes from
		// internals of mw.wikibase.entity (an index of 2 might confuse users
		// as they only gave one parameter themselves)
		$this->checkType( 'formatPropertyValues', 1, $propertyLabelOrId, 'string' );
		$this->checkTypeOptional( 'formatPropertyValues', 2, $acceptableRanks, 'table', null );
		try {
			$this->splitParserCacheIfMultilingual();

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
			return array( null );
		}
	}

}
