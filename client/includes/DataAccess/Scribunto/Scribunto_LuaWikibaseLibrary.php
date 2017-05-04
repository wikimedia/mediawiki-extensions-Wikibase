<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Deserializers\Exceptions\DeserializationException;
use Exception;
use Language;
use Scribunto_LuaLibraryBase;
use ScribuntoException;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingTermLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\EntityAccessLimitException;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @license GPL-2.0+
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

		return new EntityAccessor(
			$this->getEntityIdParser(),
			$wikibaseClient->getRestrictedEntityLookup(),
			$this->getUsageAccumulator(),
			$wikibaseClient->getAllTypesEntitySerializer(
				SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
				SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
			),
			$wikibaseClient->getPropertyDataTypeLookup(),
			$this->getLanguageFallbackChain(),
			$this->getLanguage(),
			$wikibaseClient->getTermsLanguages()
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
		$usageAccumulator = $this->getUsageAccumulator();

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			new UsageTrackingTermLookup(
				$wikibaseClient->getTermLookup(),
				$usageAccumulator
			),
			$this->getLanguageFallbackChain()
		);

		return new WikibaseLanguageDependentLuaBindings(
			$this->getEntityIdParser(),
			$labelDescriptionLookup,
			$usageAccumulator
		);
	}

	private function newLanguageIndependentLuaBindings() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new WikibaseLanguageIndependentLuaBindings(
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$wikibaseClient->getSettings(),
			$this->getUsageAccumulator(),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
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
	 * Register mw.wikibase.lua library
	 *
	 * @return array
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = array(
			'getLabel' => array( $this, 'getLabel' ),
			'getEntity' => array( $this, 'getEntity' ),
			'getSetting' => array( $this, 'getSetting' ),
			'getEntityUrl' => array( $this, 'getEntityUrl' ),
			'renderSnak' => array( $this, 'renderSnak' ),
			'formatValue' => array( $this, 'formatValue' ),
			'renderSnaks' => array( $this, 'renderSnaks' ),
			'formatValues' => array( $this, 'formatValues' ),
			'getEntityId' => array( $this, 'getEntityId' ),
			'getUserLang' => array( $this, 'getUserLang' ),
			'getDescription' => array( $this, 'getDescription' ),
			'resolvePropertyId' => array( $this, 'resolvePropertyId' ),
			'getSiteLinkPageName' => array( $this, 'getSiteLinkPageName' ),
			'incrementExpensiveFunctionCount' => array( $this, 'incrementExpensiveFunctionCount' ),
			'getPropertyOrder' => array( $this, 'getPropertyOrder' ),
			'orderProperties' => array( $this, 'orderProperties' ),
		);

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lua', $lib, array()
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
			return array( $entityArr );
		} catch ( EntityIdParsingException $ex ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		} catch ( EntityAccessLimitException $ex ) {
			throw new ScribuntoException( 'wikibase-error-exceeded-entity-access-limit' );
		} catch ( Exception $ex ) {
			throw new ScribuntoException( 'wikibase-error-serialize-error' );
		}
	}

	/**
	 * Wrapper for getEntityId in WikibaseLuaBindings
	 *
	 * @param string|null $pageTitle
	 *
	 * @return array
	 */
	public function getEntityId( $pageTitle = null ) {
		$this->checkType( 'getEntityByTitle', 1, $pageTitle, 'string' );
		return array( $this->getLanguageIndependentLuaBindings()->getEntityId( $pageTitle ) );
	}

	/**
	 * Wrapper for getSetting in WikibaseLuaBindings
	 *
	 * @param string $setting
	 *
	 * @return array
	 */
	public function getSetting( $setting ) {
		$this->checkType( 'setting', 1, $setting, 'string' );
		return array( $this->getLanguageIndependentLuaBindings()->getSetting( $setting ) );
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

	/**
	 * @param RepoLinker $repoLinker
	 */
	public function setRepoLinker( RepoLinker $repoLinker ) {
		$this->repoLinker = $repoLinker;
	}

	/**
	 * Wrapper for getLabel in Scribunto_LuaWikibaseLibraryImplementation
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
	 * Wrapper for getDescription in Scribunto_LuaWikibaseLibraryImplementation
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
	 * Wrapper for getSiteLinkPageName in WikibaseLuaBindings
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return string[]
	 */
	public function getSiteLinkPageName( $prefixedEntityId ) {
		$this->checkType( 'getSiteLinkPageName', 1, $prefixedEntityId, 'string' );
		return array( $this->getLanguageIndependentLuaBindings()->getSiteLinkPageName( $prefixedEntityId ) );
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
			$ret = array( $this->getSnakSerializationRenderer( 'escaped-plaintext' )->renderSnak( $snakSerialization ) );
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
			$ret = array( $this->getSnakSerializationRenderer( 'rich-wikitext' )->renderSnak( $snakSerialization ) );
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
			$ret = array( $this->getSnakSerializationRenderer( 'escaped-plaintext' )->renderSnaks( $snaksSerialization ) );
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
			$ret = array( $this->getSnakSerializationRenderer( 'rich-wikitext' )->renderSnaks( $snaksSerialization ) );
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
			$ret = array( $propertyId->getSerialization() );
			return $ret;
		} catch ( PropertyLabelNotResolvedException $e ) {
			return array( null );
		}
	}

	/**
	 * @param string[] $propertyIds
	 *
	 * @return array[]
	 */
	public function orderProperties( $propertyIds ) {
		if ( $propertyIds === array() ) {
			return array( array() );
		}

		$orderedPropertiesPart = array();
		$unorderedProperties = array();

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
		return array( $orderedPropertiesResult );
	}

	/**
	 * Return the order of properties as provided by the PropertyOrderProvider
	 * @return array[] either int[][] or null[][]
	 */
	public function getPropertyOrder() {
		return array( $this->getPropertyOrderProvider()->getPropertyOrder() );
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

	/**
	 * @param PropertyOrderProvider $propertyOrderProvider
	 */
	public function setPropertyOrderProvider( PropertyOrderProvider $propertyOrderProvider ) {
		$this->propertyOrderProvider = $propertyOrderProvider;
	}

}
