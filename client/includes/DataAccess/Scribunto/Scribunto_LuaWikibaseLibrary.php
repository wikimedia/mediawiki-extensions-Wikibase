<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Deserializers\Exceptions\DeserializationException;
use Exception;
use Language;
use Scribunto_LuaError;
use Scribunto_LuaLibraryBase;
use ScribuntoException;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityAccessLimitException;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingClosestReferencedEntityIdLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * @var WikibaseLanguageIndependentLuaBindings|null
	 */
	private $languageIndependentLuaBindings = null;

	/**
	 * @var WikibaseLanguageDependentLuaBindings|null
	 */
	private $languageDependentLuaBindings = null;

	/**
	 * @var EntityAccessor|null
	 */
	private $entityAccessor = null;

	/**
	 * @var SnakSerializationRenderer[]
	 */
	private $snakSerializationRenderers = [];

	/**
	 * @var LanguageFallbackChain|null
	 */
	private $fallbackChain = null;

	/**
	 * @var ParserOutputUsageAccumulator|null
	 */
	private $usageAccumulator = null;

	/**
	 * @var PropertyIdResolver|null
	 */
	private $propertyIdResolver = null;

	/**
	 * @var PropertyOrderProvider|null
	 */
	private $propertyOrderProvider = null;

	/**
	 * @var EntityIdParser|null
	 */
	private $entityIdParser = null;

	/**
	 * @var RepoLinker|null
	 */
	private $repoLinker = null;

	/**
	 * @return WikibaseLanguageIndependentLuaBindings
	 */
	private function getLanguageIndependentLuaBindings() {
		if ( $this->languageIndependentLuaBindings === null ) {
			$this->languageIndependentLuaBindings = $this->newLanguageIndependentLuaBindings();
		}

		return $this->languageIndependentLuaBindings;
	}

	/**
	 * @return WikibaseLanguageDependentLuaBindings
	 */
	private function getLanguageDependentLuaBindings() {
		if ( $this->languageDependentLuaBindings === null ) {
			$this->languageDependentLuaBindings = $this->newLanguageDependentLuaBindings();
		}

		return $this->languageDependentLuaBindings;
	}

	/**
	 * @return EntityAccessor
	 */
	private function getEntityAccessor() {
		if ( $this->entityAccessor === null ) {
			$this->entityAccessor = $this->newEntityAccessor();
		}

		return $this->entityAccessor;
	}

	/**
	 * @param string $type Either "escaped-plaintext" or "rich-wikitext".
	 *
	 * @return SnakSerializationRenderer
	 */
	private function getSnakSerializationRenderer( $type ) {
		if ( !array_key_exists( $type, $this->snakSerializationRenderers ) ) {
			$this->snakSerializationRenderers[$type] = $this->newSnakSerializationRenderer( $type );
		}

		return $this->snakSerializationRenderers[$type];
	}

	/**
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain() {
		if ( $this->fallbackChain === null ) {
			$this->fallbackChain = WikibaseClient::getDefaultInstance()->
				getDataAccessLanguageFallbackChain( $this->getLanguage() );
		}

		return $this->fallbackChain;
	}

	/**
	 * @return ParserOutputUsageAccumulator
	 */
	public function getUsageAccumulator() {
		if ( $this->usageAccumulator === null ) {
			$parserOutput = $this->getParser()->getOutput();
			$this->usageAccumulator = new ParserOutputUsageAccumulator( $parserOutput );
		}

		return $this->usageAccumulator;
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolver() {
		if ( $this->propertyIdResolver === null ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
			$propertyLabelResolver = $wikibaseClient->getStore()->getPropertyLabelResolver();

			$this->propertyIdResolver = new PropertyIdResolver(
				$entityLookup,
				$propertyLabelResolver,
				$this->getUsageAccumulator()
			);
		}

		return $this->propertyIdResolver;
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

	private function newEntityAccessor() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		return new EntityAccessor(
			$this->getEntityIdParser(),
			$wikibaseClient->getRestrictedEntityLookup(),
			$this->getUsageAccumulator(),
			$wikibaseClient->getCompactEntitySerializer(),
			$wikibaseClient->getCompactBaseDataModelSerializerFactory()->newStatementListSerializer(),
			$wikibaseClient->getPropertyDataTypeLookup(),
			$this->getLanguageFallbackChain(),
			$this->getLanguage(),
			$wikibaseClient->getTermsLanguages(),
			$settings->getSetting( 'fineGrainedLuaTracking' )
		);
	}

	/**
	 * @param string $type Either "escaped-plaintext" or "rich-wikitext".
	 *
	 * @return SnakSerializationRenderer
	 */
	private function newSnakSerializationRenderer( $type ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$snakFormatterFactory = $wikibaseClient->getDataAccessSnakFormatterFactory();
		$snakFormatter = $snakFormatterFactory->newWikitextSnakFormatter(
			$this->getLanguage(),
			$this->getUsageAccumulator(),
			$type
		);

		$snakDeserializer = $wikibaseClient->getBaseDataModelDeserializerFactory()->newSnakDeserializer();
		$snaksDeserializer = $wikibaseClient->getBaseDataModelDeserializerFactory()->newSnakListDeserializer();

		return new SnakSerializationRenderer(
			$snakFormatter,
			$snakDeserializer,
			$this->getLanguage(),
			$snaksDeserializer
		);
	}

	private function newLanguageDependentLuaBindings() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$wikibaseClient->getTermLookup(),
			$this->getLanguageFallbackChain()
		);

		$usageTrackingLabelDescriptionLookup = new UsageTrackingLanguageFallbackLabelDescriptionLookup(
			$labelDescriptionLookup,
			$this->getUsageAccumulator(),
			$this->getLanguageFallbackChain(),
			$this->allowDataAccessInUserLanguage()
		);

		return new WikibaseLanguageDependentLuaBindings(
			$this->getEntityIdParser(),
			$usageTrackingLabelDescriptionLookup
		);
	}

	private function newLanguageIndependentLuaBindings() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		return new WikibaseLanguageIndependentLuaBindings(
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$settings,
			$this->getUsageAccumulator(),
			$this->getEntityIdParser(),
			$wikibaseClient->getTermLookup(),
			$wikibaseClient->getTermsLanguages(),
			new EntityRetrievingClosestReferencedEntityIdLookup(
				$wikibaseClient->getStore()->getEntityLookup(),
				$wikibaseClient->getStore()->getEntityPrefetcher(),
				$settings->getSetting( 'referencedEntityIdMaxDepth' ),
				$settings->getSetting( 'referencedEntityIdMaxReferencedEntityVisits' )
			),
			$settings->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		if ( !$this->entityIdParser ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$this->entityIdParser = $wikibaseClient->getEntityIdParser();
		}
		return $this->entityIdParser;
	}

	/**
	 * @throws ScribuntoException
	 * @return EntityId
	 */
	private function parseUserGivenEntityId( $idSerialization ) {
		try {
			return $this->getEntityIdParser()->parse( $idSerialization );
		} catch ( EntityIdParsingException $ex ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $idSerialization ] ]
			);
		}
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @return array
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'getLabel' => [ $this, 'getLabel' ],
			'getLabelByLanguage' => [ $this, 'getLabelByLanguage' ],
			'getEntity' => [ $this, 'getEntity' ],
			'entityExists' => [ $this, 'entityExists' ],
			'getEntityStatements' => [ $this, 'getEntityStatements' ],
			'getSetting' => [ $this, 'getSetting' ],
			'getEntityUrl' => [ $this, 'getEntityUrl' ],
			'renderSnak' => [ $this, 'renderSnak' ],
			'formatValue' => [ $this, 'formatValue' ],
			'renderSnaks' => [ $this, 'renderSnaks' ],
			'formatValues' => [ $this, 'formatValues' ],
			'getEntityId' => [ $this, 'getEntityId' ],
			'getReferencedEntityId' => [ $this, 'getReferencedEntityId' ],
			'getUserLang' => [ $this, 'getUserLang' ],
			'getDescription' => [ $this, 'getDescription' ],
			'resolvePropertyId' => [ $this, 'resolvePropertyId' ],
			'getSiteLinkPageName' => [ $this, 'getSiteLinkPageName' ],
			'incrementExpensiveFunctionCount' => [ $this, 'incrementExpensiveFunctionCount' ],
			'isValidEntityId' => [ $this, 'isValidEntityId' ],
			'getPropertyOrder' => [ $this, 'getPropertyOrder' ],
			'orderProperties' => [ $this, 'orderProperties' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lua', $lib, []
		);
	}

	/**
	 * Wrapper for getEntity in EntityAccessor
	 *
	 * @param string $prefixedEntityId
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	public function getEntity( $prefixedEntityId ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );

		try {
			$entityArr = $this->getEntityAccessor()->getEntity( $prefixedEntityId );
			return [ $entityArr ];
		} catch ( EntityIdParsingException $ex ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $prefixedEntityId ] ]
			);
		} catch ( EntityAccessLimitException $ex ) {
			throw new ScribuntoException( 'wikibase-error-exceeded-entity-access-limit' );
		} catch ( Exception $ex ) {
			throw new ScribuntoException( 'wikibase-error-serialize-error' );
		}
	}

	/**
	 * Wrapper for getReferencedEntityId in WikibaseLanguageIndependentLuaBindings
	 *
	 * @param string $prefixedFromEntityId
	 * @param string $prefixedPropertyId
	 * @param string[] $prefixedToIds
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	public function getReferencedEntityId( $prefixedFromEntityId, $prefixedPropertyId, $prefixedToIds ) {
		$parserOutput = $this->getEngine()->getParser()->getOutput();
		$key = 'wikibase-referenced-entity-id-limit';

		$accesses = (int)$parserOutput->getExtensionData( $key );
		$accesses++;
		$parserOutput->setExtensionData( $key, $accesses );

		$limit = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'referencedEntityIdAccessLimit' );
		if ( $accesses > $limit ) {
			throw new Scribunto_LuaError(
				wfMessage( 'wikibase-error-exceeded-referenced-entity-id-limit' )->params( 'IGNORED' )->numParams( 3 )->text()
			);
		}

		$this->checkType( 'getReferencedEntityId', 1, $prefixedFromEntityId, 'string' );
		$this->checkType( 'getReferencedEntityId', 2, $prefixedPropertyId, 'string' );
		$this->checkType( 'getReferencedEntityId', 3, $prefixedToIds, 'table' );

		$fromId = $this->parseUserGivenEntityId( $prefixedFromEntityId );
		$propertyId = $this->parseUserGivenEntityId( $prefixedPropertyId );
		$toIds = array_map(
			[ $this, 'parseUserGivenEntityId' ],
			$prefixedToIds
		);

		if ( !( $propertyId instanceof PropertyId ) ) {
			return [ null ];
		}

		return [
			$this->getLanguageIndependentLuaBindings()->getReferencedEntityId( $fromId, $propertyId, $toIds )
		];
	}

	/**
	 * Wrapper for entityExists in EntityAccessor
	 *
	 * @param string $prefixedEntityId
	 *
	 * @throws ScribuntoException
	 * @return bool[]
	 */
	public function entityExists( $prefixedEntityId ) {
		$this->checkType( 'entityExists', 1, $prefixedEntityId, 'string' );

		try {
			return [ $this->getEntityAccessor()->entityExists( $prefixedEntityId ) ];
		} catch ( EntityIdParsingException $ex ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $prefixedEntityId ] ]
			);
		}
	}

	/**
	 * Wrapper for getEntityStatements in EntityAccessor
	 *
	 * @param string $prefixedEntityId
	 * @param string $propertyId
	 * @param string $rank Which statements to include. Either "best" or "all".
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	public function getEntityStatements( $prefixedEntityId, $propertyId, $rank ) {
		$this->checkType( 'getEntityStatements', 1, $prefixedEntityId, 'string' );
		$this->checkType( 'getEntityStatements', 2, $propertyId, 'string' );
		$this->checkType( 'getEntityStatements', 3, $rank, 'string' );

		try {
			$statements = $this->getEntityAccessor()->getEntityStatements( $prefixedEntityId, $propertyId, $rank );
		} catch ( EntityAccessLimitException $ex ) {
			throw new ScribuntoException( 'wikibase-error-exceeded-entity-access-limit' );
		} catch ( EntityIdParsingException $ex ) {
			throw new ScribuntoException(
				'wikibase-error-invalid-entity-id',
				[ 'args' => [ $prefixedEntityId ] ]
			);
		} catch ( Exception $ex ) {
			throw new ScribuntoException( 'wikibase-error-serialize-error' );
		}
		return [ $statements ];
	}

	/**
	 * Wrapper for getEntityId in WikibaseLanguageIndependentLuaBindings
	 *
	 * @param string $pageTitle
	 * @param string|null $globalSiteId
	 *
	 * @return array
	 */
	public function getEntityId( $pageTitle, $globalSiteId = null ) {
		$this->checkType( 'getEntityId', 1, $pageTitle, 'string' );
		$this->checkTypeOptional( 'getEntityId', 2, $globalSiteId, 'string', null );

		return [ $this->getLanguageIndependentLuaBindings()->getEntityId( $pageTitle, $globalSiteId ) ];
	}

	/**
	 * Wrapper for getSetting in WikibaseLanguageIndependentLuaBindings
	 *
	 * @param string $setting
	 *
	 * @return array
	 */
	public function getSetting( $setting ) {
		$this->checkType( 'setting', 1, $setting, 'string' );
		return [ $this->getLanguageIndependentLuaBindings()->getSetting( $setting ) ];
	}

	/**
	 * @param string $entityIdSerialization entity ID serialization
	 *
	 * @return string[]|null[]
	 */
	public function getEntityUrl( $entityIdSerialization ) {
		$this->checkType( 'getEntityUrl', 1, $entityIdSerialization, 'string' );

		try {
			$url = $this->getRepoLinker()->getEntityUrl(
				$this->getEntityIdParser()->parse( $entityIdSerialization )
			);
		} catch ( EntityIdParsingException $ex ) {
			$url = null;
		}

		return [ $url ];
	}

	/**
	 * @return RepoLinker
	 */
	private function getRepoLinker() {
		if ( !$this->repoLinker ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$this->repoLinker = $wikibaseClient->newRepoLinker();
		}
		return $this->repoLinker;
	}

	public function setRepoLinker( RepoLinker $repoLinker ) {
		$this->repoLinker = $repoLinker;
	}

	/**
	 * Wrapper for getLabel in WikibaseLanguageDependentLuaBindings
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return string[]|null[]
	 */
	public function getLabel( $prefixedEntityId ) {
		$this->checkType( 'getLabel', 1, $prefixedEntityId, 'string' );

		return $this->getLanguageDependentLuaBindings()->getLabel( $prefixedEntityId );
	}

	/**
	 * Wrapper for getLabelByLanguage in WikibaseLanguageIndependentLuaBindings
	 *
	 * @param string $prefixedEntityId
	 * @param string $languageCode
	 *
	 * @return string[]|null[]
	 */
	public function getLabelByLanguage( $prefixedEntityId, $languageCode ) {
		$this->checkType( 'getLabelByLanguage', 1, $prefixedEntityId, 'string' );
		$this->checkType( 'getLabelByLanguage', 2, $languageCode, 'string' );

		return [ $this->getLanguageIndependentLuaBindings()->getLabelByLanguage( $prefixedEntityId, $languageCode ) ];
	}

	/**
	 * Wrapper for getDescription in WikibaseLanguageDependentLuaBindings
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return string[]|null[]
	 */
	public function getDescription( $prefixedEntityId ) {
		$this->checkType( 'getDescription', 1, $prefixedEntityId, 'string' );

		return $this->getLanguageDependentLuaBindings()->getDescription( $prefixedEntityId );
	}

	/**
	 * Wrapper for getSiteLinkPageName in WikibaseLanguageIndependentLuaBindings
	 *
	 * @param string $prefixedItemId
	 * @param string|null $globalSiteId
	 *
	 * @return string[]
	 */
	public function getSiteLinkPageName( $prefixedItemId, $globalSiteId ) {
		$this->checkType( 'getSiteLinkPageName', 1, $prefixedItemId, 'string' );
		$this->checkTypeOptional( 'getSiteLinkPageName', 2, $globalSiteId, 'string', null );

		return [ $this->getLanguageIndependentLuaBindings()->getSiteLinkPageName( $prefixedItemId, $globalSiteId ) ];
	}

	/**
	 * Wrapper for WikibaseLanguageIndependentLuaBindings::isValidEntityId
	 *
	 * @param string $entityIdSerialization
	 *
	 * @throws ScribuntoException
	 * @return bool[] One bool telling whether the entity id is valid (parseable).
	 */
	public function isValidEntityId( $entityIdSerialization ) {
		$this->checkType( 'isValidEntityId', 1, $entityIdSerialization, 'string' );

		return [ $this->getLanguageIndependentLuaBindings()->isValidEntityId( $entityIdSerialization ) ];
	}

	/**
	 * Wrapper for SnakSerializationRenderer::renderSnak, set to output wikitext escaped plain text.
	 *
	 * @param array $snakSerialization
	 *
	 * @throws ScribuntoException
	 * @return string[] Wikitext
	 */
	public function renderSnak( $snakSerialization ) {
		$this->checkType( 'renderSnak', 1, $snakSerialization, 'table' );

		try {
			$ret = [ $this->getSnakSerializationRenderer( 'escaped-plaintext' )->renderSnak( $snakSerialization ) ];
			return $ret;
		} catch ( DeserializationException $e ) {
			throw new ScribuntoException( 'wikibase-error-deserialize-error' );
		}
	}

	/**
	 * Wrapper for SnakSerializationRenderer::renderSnak, set to output rich wikitext.
	 *
	 * @param array $snakSerialization
	 *
	 * @throws ScribuntoException
	 * @return string[] Wikitext
	 */
	public function formatValue( $snakSerialization ) {
		$this->checkType( 'formatValue', 1, $snakSerialization, 'table' );

		try {
			$ret = [ $this->getSnakSerializationRenderer( 'rich-wikitext' )->renderSnak( $snakSerialization ) ];
			return $ret;
		} catch ( DeserializationException $e ) {
			throw new ScribuntoException( 'wikibase-error-deserialize-error' );
		}
	}

	/**
	 * Wrapper for SnakSerializationRenderer::renderSnaks, set to output wikitext escaped plain text.
	 *
	 * @param array[] $snaksSerialization
	 *
	 * @throws ScribuntoException
	 * @return string[] Wikitext
	 */
	public function renderSnaks( $snaksSerialization ) {
		$this->checkType( 'renderSnaks', 1, $snaksSerialization, 'table' );

		try {
			$ret = [ $this->getSnakSerializationRenderer( 'escaped-plaintext' )->renderSnaks( $snaksSerialization ) ];
			return $ret;
		} catch ( DeserializationException $e ) {
			throw new ScribuntoException( 'wikibase-error-deserialize-error' );
		}
	}

	/**
	 * Wrapper for SnakSerializationRenderer::renderSnaks, set to output rich wikitext.
	 *
	 * @param array[] $snaksSerialization
	 *
	 * @throws ScribuntoException
	 * @return string[] Wikitext
	 */
	public function formatValues( $snaksSerialization ) {
		$this->checkType( 'formatValues', 1, $snaksSerialization, 'table' );

		try {
			$ret = [ $this->getSnakSerializationRenderer( 'rich-wikitext' )->renderSnaks( $snaksSerialization ) ];
			return $ret;
		} catch ( DeserializationException $e ) {
			throw new ScribuntoException( 'wikibase-error-deserialize-error' );
		}
	}

	/**
	 * Wrapper for PropertyIdResolver
	 *
	 * @param string $propertyLabelOrId
	 *
	 * @return string[]|null[]
	 */
	public function resolvePropertyId( $propertyLabelOrId ) {
		global $wgContLang;

		$this->checkType( 'resolvePropertyId', 1, $propertyLabelOrId, 'string' );
		try {
			$propertyId = $this->getPropertyIdResolver()->resolvePropertyId( $propertyLabelOrId, $wgContLang->getCode() );
			$ret = [ $propertyId->getSerialization() ];
			return $ret;
		} catch ( PropertyLabelNotResolvedException $e ) {
			return [ null ];
		}
	}

	/**
	 * @param string[] $propertyIds
	 *
	 * @return array[]
	 */
	public function orderProperties( array $propertyIds ) {
		if ( $propertyIds === [] ) {
			return [ [] ];
		}

		$orderedPropertiesPart = [];
		$unorderedProperties = [];

		$propertyOrder = $this->getPropertyOrderProvider()->getPropertyOrder();
		foreach ( $propertyIds as $propertyId ) {
			if ( isset( $propertyOrder[$propertyId] ) ) {
				$orderedPropertiesPart[ $propertyOrder[ $propertyId ] ] = $propertyId;
			} else {
				$unorderedProperties[] = $propertyId;
			}
		}
		ksort( $orderedPropertiesPart );
		$orderedProperties = array_merge( $orderedPropertiesPart, $unorderedProperties );

		// Lua tables start at 1
		$orderedPropertiesResult = array_combine(
				range( 1, count( $orderedProperties ) ), array_values( $orderedProperties )
		);
		return [ $orderedPropertiesResult ];
	}

	/**
	 * Return the order of properties as provided by the PropertyOrderProvider
	 * @return array[] either int[][] or null[][]
	 */
	public function getPropertyOrder() {
		return [ $this->getPropertyOrderProvider()->getPropertyOrder() ];
	}

	/**
	 * @return PropertyOrderProvider
	 */
	private function getPropertyOrderProvider() {
		if ( !$this->propertyOrderProvider ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$this->propertyOrderProvider = $wikibaseClient->getPropertyOrderProvider();
		}
		return $this->propertyOrderProvider;
	}

	public function setPropertyOrderProvider( PropertyOrderProvider $propertyOrderProvider ) {
		$this->propertyOrderProvider = $propertyOrderProvider;
	}

}
