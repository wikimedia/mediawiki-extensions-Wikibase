<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Language;
use MediaWiki\Extension\Scribunto\ScribuntoException;
use MediaWiki\MediaWikiServices;
use Scribunto_LuaLibraryBase;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Andrew Hall
 */
class Scribunto_LuaWikibaseEntityLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLuaEntityBindings|null
	 */
	private $wbLibrary;

	/**
	 * @var LuaFunctionCallTracker|null
	 */
	private $luaFunctionCallTracker = null;

	private function getImplementation(): WikibaseLuaEntityBindings {
		if ( !$this->wbLibrary ) {
			$this->wbLibrary = $this->newImplementation();
		}

		return $this->wbLibrary;
	}

	private function newImplementation(): WikibaseLuaEntityBindings {
		$lang = $this->getLanguage();
		$snakFormatterFactory = WikibaseClient::getDataAccessSnakFormatterFactory();
		$plainTextSnakFormatter = $snakFormatterFactory->newWikitextSnakFormatter(
			$lang,
			$this->getUsageAccumulator()
		);
		$richWikitextSnakFormatter = $this->newRichWikitextSnakFormatter(
			$snakFormatterFactory,
			$lang
		);

		$entityLookup = WikibaseClient::getRestrictedEntityLookup();

		$propertyIdResolver = new PropertyIdResolver(
			$entityLookup,
			WikibaseClient::getPropertyLabelResolver(),
			$this->getUsageAccumulator()
		);

		$plainTextTransclusionInteractor = new StatementTransclusionInteractor(
			$lang,
			$propertyIdResolver,
			new SnaksFinder(),
			$plainTextSnakFormatter,
			$entityLookup,
			$this->getUsageAccumulator()
		);
		$richWikitextTransclusionInteractor = new StatementTransclusionInteractor(
			$lang,
			$propertyIdResolver,
			new SnaksFinder(),
			$richWikitextSnakFormatter,
			$entityLookup,
			$this->getUsageAccumulator()
		);

		return new WikibaseLuaEntityBindings(
			$plainTextTransclusionInteractor,
			$richWikitextTransclusionInteractor,
			WikibaseClient::getEntityIdParser(),
			WikibaseClient::getTermsLanguages(),
			$lang,
			$this->getUsageAccumulator(),
			WikibaseClient::getSettings()->getSetting( 'siteGlobalID' )
		);
	}

	private function newRichWikitextSnakFormatter(
		DataAccessSnakFormatterFactory $snakFormatterFactory,
		Language $lang
	): WikitextPreprocessingSnakFormatter {
		$innerFormatter = $snakFormatterFactory->newWikitextSnakFormatter(
			$lang,
			$this->getUsageAccumulator(),
			DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT
		);

		// As Scribunto doesn't strip parser tags (like <mapframe>) itself,
		// we need to take care of that.
		return new WikitextPreprocessingSnakFormatter(
			$innerFormatter,
			$this->getParser()
		);
	}

	private function getLuaFunctionCallTracker(): LuaFunctionCallTracker {
		if ( !$this->luaFunctionCallTracker ) {
			$mwServices = MediaWikiServices::getInstance();
			$settings = WikibaseClient::getSettings( $mwServices );

			$this->luaFunctionCallTracker = new LuaFunctionCallTracker(
				$mwServices->getStatsdDataFactory(),
				$settings->getSetting( 'siteGlobalID' ),
				WikibaseClient::getSiteGroup( $mwServices ),
				$settings->getSetting( 'trackLuaFunctionCallsPerSiteGroup' ),
				$settings->getSetting( 'trackLuaFunctionCallsPerWiki' ),
				$settings->getSetting( 'trackLuaFunctionCallsSampleRate' )
			);
		}

		return $this->luaFunctionCallTracker;
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
	 */
	private function getLanguage(): Language {
		if ( $this->allowDataAccessInUserLanguage() && $this->getParser() ) {
			return $this->getParserOptions()->getUserLangObj();
		}

		return MediaWikiServices::getInstance()->getContentLanguage();
	}

	private function allowDataAccessInUserLanguage(): bool {
		$settings = WikibaseClient::getSettings();

		return $settings->getSetting( 'allowDataAccessInUserLanguage' );
	}

	public function getUsageAccumulator(): UsageAccumulator {
		return WikibaseClient::getUsageAccumulatorFactory()
			->newFromParserOutput( $this->getParser()->getOutput() );
	}

	/**
	 * Add a statement usage (called once specific statements are accessed).
	 *
	 * @param string $entityId The Entity from which the statements were accessed.
	 * @param string $propertyId Property id of the statements accessed.
	 */
	public function addStatementUsage( string $entityId, string $propertyId ): void {
		$this->getImplementation()->addStatementUsage( $entityId, $propertyId );
	}

	/**
	 * Add a label usage (called once specific labels are accessed).
	 *
	 * @param string $entityId The Entity from which the labels were accessed.
	 * @param string|null $langCode Language code of the labels accessed.
	 */
	public function addLabelUsage( string $entityId, ?string $langCode ): void {
		$this->getImplementation()->addLabelUsage( $entityId, $langCode );
	}

	/**
	 * Add a description usage (called once specific descriptions are accessed).
	 *
	 * @param string $entityId The Entity from which the descriptions were accessed.
	 * @param string|null $langCode Language code of the descriptions accessed.
	 */
	public function addDescriptionUsage( string $entityId, ?string $langCode ): void {
		$this->getImplementation()->addDescriptionUsage( $entityId, $langCode );
	}

	/**
	 * Add a sitelinks usage (called once specific sitelinks are accessed).
	 *
	 * @param string $entityId The Entity from which the sitelinks were accessed.
	 */
	public function addSiteLinksUsage( string $entityId ): void {
		$this->getImplementation()->addSiteLinksUsage( $entityId );
	}

	/**
	 * Add an other (O) usage (called once the otherwise not covered aspect is used).
	 *
	 * @param string $entityId The Entity from which something was accessed.
	 */
	public function addOtherUsage( string $entityId ): void {
		$this->getImplementation()->addOtherUsage( $entityId );
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
			'formatStatements' => [ $this, 'formatStatements' ],
			'formatPropertyValues' => [ $this, 'formatPropertyValues' ],
			'addStatementUsage' => [ $this, 'addStatementUsage' ],
			'addLabelUsage' => [ $this, 'addLabelUsage' ],
			'addDescriptionUsage' => [ $this, 'addDescriptionUsage' ],
			'addSiteLinksUsage' => [ $this, 'addSiteLinksUsage' ],
			'addOtherUsage' => [ $this, 'addOtherUsage' ],
			'incrementStatsKey' => [ $this, 'incrementStatsKey' ],
		];

		$settings = WikibaseClient::getSettings();
		// These settings will be exposed to the Lua module.
		$options = [
			'trackLuaFunctionCallsSampleRate' => $settings->getSetting( 'trackLuaFunctionCallsSampleRate' ),
			'languageCode' => $this->getLanguage()->getCode(),
			'globalSiteId' => $settings->getSetting( 'siteGlobalID' ),
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.entity.lua', $lib, $options
		);
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a NumericPropertyId
	 * or the label of a Property) as wikitext escaped plain text.
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws ScribuntoException
	 * @return string[]|null[]
	 */
	public function formatPropertyValues( string $entityId, string $propertyLabelOrId, array $acceptableRanks = null ): array {
		try {
			return [
				$this->getImplementation()->formatPropertyValues(
					$entityId,
					$propertyLabelOrId,
					$acceptableRanks
				),
			];
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $entityId ] ]
			);
		} catch ( PropertyLabelNotResolvedException $e ) {
			return [ null ];
		}
	}

	/**
	 * Format the main Snaks belonging to a Statement (which is identified by a NumericPropertyId
	 * or the label of a Property) as rich wikitext.
	 *
	 * @param string $entityId
	 * @param string $propertyLabelOrId
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws ScribuntoException
	 * @return string[]|null[]
	 */
	public function formatStatements( string $entityId, string $propertyLabelOrId, array $acceptableRanks = null ): array {
		try {
			return [
				$this->getImplementation()->formatStatements(
					$entityId,
					$propertyLabelOrId,
					$acceptableRanks
				),
			];
		} catch ( InvalidArgumentException $e ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $entityId ] ]
			);
		} catch ( PropertyLabelNotResolvedException $e ) {
			return [ null ];
		}
	}

	/**
	 * Increment the given stats key.
	 */
	public function incrementStatsKey( string $key ): void {
		$this->getLuaFunctionCallTracker()->incrementKey( $key );
	}

}
