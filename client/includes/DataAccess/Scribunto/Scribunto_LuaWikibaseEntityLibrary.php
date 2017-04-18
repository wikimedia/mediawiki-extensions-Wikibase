<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Language;
use Scribunto_LuaLibraryBase;
use ScribuntoException;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
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
		$snakFormatterFactory = $wikibaseClient->getDataAccessSnakFormatterFactory();
		$plainTextSnakFormatter = $snakFormatterFactory->newWikitextSnakFormatter(
			$lang,
			$this->getUsageAccumulator()
		);
		$richWikitextSnakFormatter = $snakFormatterFactory->newWikitextSnakFormatter(
			$lang,
			$this->getUsageAccumulator(),
			'rich-wikitext'
		);

		$entityLookup = $wikibaseClient->getRestrictedEntityLookup();

		$propertyIdResolver = new PropertyIdResolver(
			$entityLookup,
			$wikibaseClient->getStore()->getPropertyLabelResolver(),
			$this->getUsageAccumulator()
		);

		$plainTextTransclusionInteractor = new StatementTransclusionInteractor(
			$lang,
			$propertyIdResolver,
			new SnaksFinder(),
			$plainTextSnakFormatter,
			$entityLookup
		);
		$richWikitextTransclusionInteractor = new StatementTransclusionInteractor(
			$lang,
			$propertyIdResolver,
			new SnaksFinder(),
			$richWikitextSnakFormatter,
			$entityLookup
		);

		return new WikibaseLuaEntityBindings(
			$plainTextTransclusionInteractor,
			$richWikitextTransclusionInteractor,
			$wikibaseClient->getEntityIdParser(),
			$lang,
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * Returns the language to use. If we are on a multilingual wiki
	 * (allowDataAccessInUserLanguage is true) this will be the user's interface
	 * language, otherwise it will be the content language.
	 * In a perfect world, this would equal Parser::getTargetLanguage.
	 *
	 * This can probably be removed after T114640 has been implemented.
	 *
	 * Please note, that this splits the parser cache by user language, if
	 * allowDataAccessInUserLanguage is true.
	 *
	 * @return Language
	 */
	private function getLanguage() {
		global $wgContLang;

		if ( $this->allowDataAccessInUserLanguage() ) {
			return $this->getParserOptions()->getUserLangObj();
		}

		return $wgContLang;
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
	 * Register mw.wikibase.entity.lua library
	 *
	 * @return array
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'getGlobalSiteId' => [ $this, 'getGlobalSiteId' ],
			'getLanguageCode' => [ $this, 'getLanguageCode' ],
			'formatStatements' => [ $this, 'formatStatements' ],
			'formatPropertyValues' => [ $this, 'formatPropertyValues' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.entity.lua', $lib, []
		);
	}

	/**
	 * Wrapper for getGlobalSiteId in WikibaseLuaEntityBindings
	 *
	 * @return string[]
	 */
	public function getGlobalSiteId() {
		return [ $this->getImplementation()->getGlobalSiteId() ];
	}

	/**
	 * Wrapper for getLanguageCode in WikibaseLuaEntityBindings
	 *
	 * @return string[]
	 */
	public function getLanguageCode() {
		return [ $this->getImplementation()->getLanguageCode() ];
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a PropertyId
	 * or the label of a Property) as wikitext escaped plain text.
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
			return [
				$this->getImplementation()->formatPropertyValues(
					$entityId,
					$propertyLabelOrId,
					$acceptableRanks
				)
			];
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		} catch ( PropertyLabelNotResolvedException $e ) {
			return [ null ];
		}
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a PropertyId
	 * or the label of a Property) as rich wikitext.
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws ScribuntoException
	 * @return string[]|null[]
	 */
	public function formatStatements( $entityId, $propertyLabelOrId, array $acceptableRanks = null ) {
		$this->checkType( 'formatStatements', 0, $entityId, 'string' );
		// Use 1 as index for the property id, as the first parameter comes from
		// internals of mw.wikibase.entity (an index of 2 might confuse users
		// as they only gave one parameter themselves)
		$this->checkType( 'formatStatements', 1, $propertyLabelOrId, 'string' );
		$this->checkTypeOptional( 'formatStatements', 2, $acceptableRanks, 'table', null );
		try {
			return [
				$this->getImplementation()->formatStatements(
					$entityId,
					$propertyLabelOrId,
					$acceptableRanks
				)
			];
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		} catch ( PropertyLabelNotResolvedException $e ) {
			return [ null ];
		}
	}

}
