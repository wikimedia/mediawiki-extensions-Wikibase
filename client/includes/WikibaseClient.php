<?php

namespace Wikibase\Client;

use CachedBagOStuff;
use InvalidArgumentException;
use MWNamespace;
use ObjectCache;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Changes\CentralIdLookupFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeFactory;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use ExtensionRegistry;
use ExternalUserNames;
use Hooks;
use Http;
use JobQueueGroup;
use Language;
use LogicException;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiSite;
use MWException;
use Parser;
use Serializers\Serializer;
use Site;
use SiteLookup;
use StubObject;
use Title;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ClientSiteLinkTitleLookup;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Store\ClientStore;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\DisabledEntityTypesEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\ItemChange;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdMissRecordingSimpleCache;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Lib\Store\TermPropertyLabelResolver;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\SettingsArray;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\StringNormalizer;
use Wikibase\WikibaseSettings;

/**
 * Top level factory for the WikibaseClient extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
final class WikibaseClient {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var WikibaseServices
	 */
	private $wikibaseServices;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $propertyDataTypeLookup = null;

	/**
	 * @var DataTypeFactory|null
	 */
	private $dataTypeFactory = null;

	/**
	 * @var Deserializer|null
	 */
	private $entityDeserializer = null;

	/**
	 * @var EntityIdParser|null
	 */
	private $entityIdParser = null;

	/**
	 * @var EntityIdComposer|null
	 */
	private $entityIdComposer = null;

	/**
	 * @var ClientStore|null
	 */
	private $store = null;

	/**
	 * @var Site|null
	 */
	private $site = null;

	/**
	 * @var string|null
	 */
	private $siteGroup = null;

	/**
	 * @var OutputFormatSnakFormatterFactory|null
	 */
	private $snakFormatterFactory = null;

	/**
	 * @var OutputFormatValueFormatterFactory|null
	 */
	private $valueFormatterFactory = null;

	/**
	 * @var LangLinkHandler|null
	 */
	private $langLinkHandler = null;

	/**
	 * @var ClientParserOutputDataUpdater|null
	 */
	private $parserOutputDataUpdater = null;

	/**
	 * @var NamespaceChecker|null
	 */
	private $namespaceChecker = null;

	/**
	 * @var RestrictedEntityLookup|null
	 */
	private $restrictedEntityLookup = null;

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var RepositoryDefinitions
	 */
	private $repositoryDefinitions;

	/**
	 * @var TermLookup|null
	 */
	private $termLookup = null;

	/**
	 * @var TermBuffer|null
	 */
	private $termBuffer = null;

	/**
	 * @var PrefetchingTermLookup|null
	 */
	private $prefetchingTermLookup = null;

	/**
	 * @var PropertyOrderProvider|null
	 */
	private $propertyOrderProvider = null;

	/**
	 * @var SidebarLinkBadgeDisplay|null
	 */
	private $sidebarLinkBadgeDisplay = null;

	/**
	 * @var WikibaseValueFormatterBuilders|null
	 */
	private $valueFormatterBuilders = null;

	/**
	 * @var WikibaseContentLanguages|null
	 */
	private $wikibaseContentLanguages = null;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	private $itemTermIndex = null;

	private $descriptionLookup = null;

	private $propertyLabelResolver = null;

	/**
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders() {
		return self::getDefaultInstance()->newWikibaseValueFormatterBuilders();
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders() {
		if ( $this->valueFormatterBuilders === null ) {
			$entityTitleLookup = new ClientSiteLinkTitleLookup(
				$this->getStore()->getSiteLinkLookup(),
				$this->settings->getSetting( 'siteGlobalID' )
			);

			$kartographerEmbeddingHandler = null;
			if ( $this->useKartographerGlobeCoordinateFormatter() ) {
				$kartographerEmbeddingHandler = new CachingKartographerEmbeddingHandler( new Parser() );
			}

			$this->valueFormatterBuilders = new WikibaseValueFormatterBuilders(
				$this->getContentLanguage(),
				new FormatterLabelDescriptionLookupFactory( $this->getTermLookup() ),
				new LanguageNameLookup( $this->getUserLanguage()->getCode() ),
				$this->getRepoItemUriParser(),
				$this->settings->getSetting( 'geoShapeStorageBaseUrl' ),
				$this->settings->getSetting( 'tabularDataStorageBaseUrl' ),
				$this->getFormatterCache(),
				$this->settings->getSetting( 'sharedCacheDuration' ),
				$this->getEntityLookup(),
				$this->getStore()->getEntityRevisionLookup(),
				$entityTitleLookup,
				$kartographerEmbeddingHandler,
				$this->settings->getSetting( 'useKartographerMaplinkInWikitext' )
			);
		}

		return $this->valueFormatterBuilders;
	}

	/**
	 * @return bool
	 */
	private function useKartographerGlobeCoordinateFormatter() {
		// FIXME: remove the global out of here
		global $wgKartographerEnableMapFrame;

		return $this->settings->getSetting( 'useKartographerGlobeCoordinateFormatter' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' ) &&
			isset( $wgKartographerEnableMapFrame ) &&
			$wgKartographerEnableMapFrame;
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	public static function getDefaultSnakFormatterBuilders() {
		static $builders;

		if ( $builders === null ) {
			$builders = self::getDefaultInstance()->newWikibaseSnakFormatterBuilders(
				self::getDefaultValueFormatterBuilders()
			);
		}

		return $builders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	private function newWikibaseSnakFormatterBuilders( WikibaseValueFormatterBuilders $valueFormatterBuilders ) {
		return new WikibaseSnakFormatterBuilders(
			$valueFormatterBuilders,
			$this->getStore()->getPropertyInfoLookup(),
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory()
		);
	}

	public function __construct(
		SettingsArray $settings,
		DataTypeDefinitions $dataTypeDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		RepositoryDefinitions $repositoryDefinitions,
		SiteLookup $siteLookup,
		EntitySourceDefinitions $entitySourceDefinitions
	) {
		$this->settings = $settings;
		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->repositoryDefinitions = $repositoryDefinitions;
		$this->siteLookup = $siteLookup;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
	}

	/**
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		if ( $this->dataTypeFactory === null ) {
			$this->dataTypeFactory = new DataTypeFactory( $this->dataTypeDefinitions->getValueTypes() );
		}

		return $this->dataTypeFactory;
	}

	/**
	 * @return EntityIdParser
	 */
	public function getEntityIdParser() {
		if ( $this->entityIdParser === null ) {
			$this->entityIdParser = new DispatchingEntityIdParser(
				$this->entityTypeDefinitions->getEntityIdBuilders()
			);
		}

		return $this->entityIdParser;
	}

	/**
	 * @return EntityIdComposer
	 */
	public function getEntityIdComposer() {
		if ( $this->entityIdComposer === null ) {
			$this->entityIdComposer = new EntityIdComposer(
				$this->entityTypeDefinitions->getEntityIdComposers()
			);
		}

		return $this->entityIdComposer;
	}

	/**
	 * @return WikibaseServices
	 */
	public function getWikibaseServices() {
		if ( $this->wikibaseServices === null ) {
			$this->wikibaseServices = $this->settings->getSetting( 'useEntitySourceBasedFederation' ) ?
				$this->newEntitySourceWikibaseServices() :
				$this->newMultipleRepositoryAwareWikibaseServices();
		}

		return $this->wikibaseServices;
	}

	private function newMultipleRepositoryAwareWikibaseServices() {
		return new MultipleRepositoryAwareWikibaseServices(
			$this->getEntityIdParser(),
			$this->getEntityIdComposer(),
			$this->repositoryDefinitions,
			$this->entityTypeDefinitions,
			$this->getDataAccessSettings(),
			$this->getMultiRepositoryServiceWiring(),
			$this->getPerRepositoryServiceWiring(),
			MediaWikiServices::getInstance()->getNameTableStoreFactory()
		);
	}

	private function newEntitySourceWikibaseServices() {
		$nameTableStoreFactory = MediaWikiServices::getInstance()->getNameTableStoreFactory();
		$genericServices = new GenericServices(
			$this->entityTypeDefinitions,
			$this->repositoryDefinitions->getEntityNamespaces(),
			$this->repositoryDefinitions->getEntitySlots()
		);

		$singleSourceServices = [];

		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			// TODO: extract
			$singleSourceServices[$source->getSourceName()] = new SingleEntitySourceServices(
				$genericServices,
				$this->getEntityIdParser(),
				$this->getEntityIdComposer(),
				$this->getDataValueDeserializer(),
				$nameTableStoreFactory->getSlotRoles( $source->getDatabaseName() ),
				$this->getDataAccessSettings(),
				$source,
				$this->entityTypeDefinitions->getDeserializerFactoryCallbacks(),
				$this->entityTypeDefinitions->getEntityMetaDataAccessorCallbacks()
			);
		}

		return new MultipleEntitySourceServices( $this->entitySourceDefinitions, $genericServices, $singleSourceServices );
	}

	private function getDataAccessSettings() {
		return new DataAccessSettings(
			$this->settings->getSetting( 'maxSerializedEntitySize' ),
			$this->settings->getSetting( 'useTermsTableSearchFields' ),
			$this->settings->getSetting( 'forceWriteTermsTableSearchFields' ),
			$this->settings->getSetting( 'useEntitySourceBasedFederation' )
		);
	}

	private function getMultiRepositoryServiceWiring() {
		global $wgWikibaseMultiRepositoryServiceWiringFiles;

		$wiring = [];
		foreach ( $wgWikibaseMultiRepositoryServiceWiringFiles as $file ) {
			$wiring = array_merge(
				$wiring,
				require $file
			);
		}
		return $wiring;
	}

	private function getPerRepositoryServiceWiring() {
		global $wgWikibasePerRepositoryServiceWiringFiles;

		$wiring = [];
		foreach ( $wgWikibasePerRepositoryServiceWiringFiles as $file ) {
			$wiring = array_merge(
				$wiring,
				require $file
			);
		}
		return $wiring;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		return $this->getStore()->getEntityLookup();
	}

	/**
	 * @return array[]
	 */
	private static function getDefaultEntityTypes() {
		return require __DIR__ . '/../../lib/WikibaseLib.entitytypes.php';
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		if ( !$this->termBuffer ) {
			$this->termBuffer = $this->getPrefetchingTermLookup();
		}

		return $this->termBuffer;
	}

	/**
	 * @return TermLookup
	 */
	public function getTermLookup() {
		if ( !$this->termLookup ) {
			$this->termLookup = $this->getPrefetchingTermLookup();
		}

		return $this->termLookup;
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	private function getPrefetchingTermLookup() {
		if ( !$this->prefetchingTermLookup ) {
			// TODO: This should not assume the TermBuffer instance to be a PrefetchingTermLookup
			$this->prefetchingTermLookup = $this->getWikibaseServices()->getTermBuffer();
		}

		return $this->prefetchingTermLookup;
	}

	/**
	 * @param string $displayLanguageCode
	 *
	 * XXX: This is not used by client itself, but is used by ArticlePlaceholder!
	 *
	 * @return TermSearchInteractor
	 */
	public function newTermSearchInteractor( $displayLanguageCode ) {
		return $this->getWikibaseServices()->getTermSearchInteractorFactory()
			->newInteractor( $displayLanguageCode );
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	public function getPropertyDataTypeLookup() {
		if ( $this->propertyDataTypeLookup === null ) {
			$infoLookup = $this->getStore()->getPropertyInfoLookup();
			$retrievingLookup = new EntityRetrievingDataTypeLookup( $this->getEntityLookup() );
			$this->propertyDataTypeLookup = new PropertyInfoDataTypeLookup(
				$infoLookup,
				$this->getLogger(),
				$retrievingLookup
			);
		}

		return $this->propertyDataTypeLookup;
	}

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		return $this->getWikibaseServices()->getStringNormalizer();
	}

	/**
	 * @return RepoLinker
	 */
	public function newRepoLinker() {
		$dataAccessSettings = $this->getDataAccessSettings();

		return new RepoLinker(
			$dataAccessSettings,
			$this->entitySourceDefinitions,
			$this->settings->getSetting( 'repoUrl' ),
			$dataAccessSettings->useEntitySourceBasedFederation() ?
				$this->entitySourceDefinitions->getConceptBaseUris() :
				$this->repositoryDefinitions->getConceptBaseUris(),
			$this->settings->getSetting( 'repoArticlePath' ),
			$this->settings->getSetting( 'repoScriptPath' )
		);
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		return $this->getWikibaseServices()->getLanguageFallbackChainFactory();
	}

	/**
	 * @return LanguageFallbackLabelDescriptionLookupFactory
	 */
	public function getLanguageFallbackLabelDescriptionLookupFactory() {
		return new LanguageFallbackLabelDescriptionLookupFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getTermLookup(),
			$this->getTermBuffer()
		);
	}

	/**
	 * Returns an instance of the default store.
	 *
	 * @return ClientStore
	 */
	public function getStore() {
		if ( $this->store === null ) {
			$this->store = new DirectSqlStore(
				$this->getEntityChangeFactory(),
				$this->getEntityIdParser(),
				$this->getEntityIdComposer(),
				$this->getEntityNamespaceLookup(),
				$this->getWikibaseServices(),
				$this->getSettings(),
				$this->getRepositoryDefinitions()->getDatabaseNames()[''],
				$this->getContentLanguage()->getCode()
			);
		}

		return $this->store;
	}

	/**
	 * Overrides the default store to be used in the client app context.
	 * This is intended for use by test cases.
	 *
	 * @param ClientStore|null $store
	 *
	 * @throws LogicException If MW_PHPUNIT_TEST is not defined, to avoid this
	 * method being abused in production code.
	 */
	public function overrideStore( ClientStore $store = null ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new LogicException( 'Overriding the store instance is only supported in test mode' );
		}

		$this->store = $store;
	}

	/**
	 * Overrides the TermLookup to be used.
	 * This is intended for use by test cases.
	 *
	 * @param TermLookup|null $lookup
	 *
	 * @throws LogicException If MW_PHPUNIT_TEST is not defined, to avoid this
	 * method being abused in production code.
	 */
	public function overrideTermLookup( TermLookup $lookup = null ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new LogicException( 'Overriding TermLookup is only supported in test mode' );
		}

		$this->termLookup = $lookup;
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	public function getContentLanguage() {
		global $wgContLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgContLang in the constructor, because it may still be null
		// when WikibaseClient is initialized. In particular, the language object may not yet
		// be there when the SetupAfterCache hook is run during bootstrapping.

		if ( !$wgContLang ) {
			throw new MWException( 'Premature access: $wgContLang is not yet initialized!' );
		}

		StubObject::unstub( $wgContLang );
		return $wgContLang;
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	private function getUserLanguage() {
		global $wgLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgLang in the constructor, because it may still be null
		// when WikibaseClient is initialized. In particular, the language object may not yet
		// be there when the SetupAfterCache hook is run during bootstrapping.

		if ( !$wgLang ) {
			throw new MWException( 'Premature access: $wgLang is not yet initialized!' );
		}

		StubObject::unstub( $wgLang );
		return $wgLang;
	}

	/**
	 * @return SettingsArray
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Returns a new instance constructed from global settings.
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @throws MWException
	 * @return self
	 */
	private static function newInstance() {
		global $wgWBClientDataTypes;

		if ( !is_array( $wgWBClientDataTypes ) ) {
			throw new MWException( '$wgWBClientDataTypes must be array. '
				. 'Maybe you forgot to require WikibaseClient.php in your LocalSettings.php?' );
		}

		$dataTypeDefinitions = $wgWBClientDataTypes;
		Hooks::run( 'WikibaseClientDataTypes', [ &$dataTypeDefinitions ] );

		$entityTypeDefinitionsArray = self::getDefaultEntityTypes();
		Hooks::run( 'WikibaseClientEntityTypes', [ &$entityTypeDefinitionsArray ] );

		$settings = WikibaseSettings::getClientSettings();

		$entityTypeDefinitions = new EntityTypeDefinitions( $entityTypeDefinitionsArray );

		return new self(
			$settings,
			new DataTypeDefinitions(
				$dataTypeDefinitions,
				$settings->getSetting( 'disabledDataTypes' )
			),
			$entityTypeDefinitions,
			self::getRepositoryDefinitionsFromSettings( $settings, $entityTypeDefinitions ),
			MediaWikiServices::getInstance()->getSiteLookup(),
			self::getEntitySourceDefinitionsFromSettings( $settings )
		);
	}

	/**
	 *
	 * @param SettingsArray $settings
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 *
	 * @return RepositoryDefinitions
	 */
	private static function getRepositoryDefinitionsFromSettings( SettingsArray $settings, EntityTypeDefinitions $entityTypeDefinitions ) {
		$definitions = [];

		// Backwards compatibility: if the old "foreignRepositories" settings is there,
		// use its values.
		$repoSettingsArray = $settings->hasSetting( 'foreignRepositories' )
			? $settings->getSetting( 'foreignRepositories' )
			: $settings->getSetting( 'repositories' );

		// Backwards compatibility: if settings of the "local" repository
		// are not defined in the "repositories" settings but with individual settings,
		// fallback to old single-repo settings
		if ( $settings->hasSetting( 'repoDatabase' )
			&& $settings->hasSetting( 'entityNamespaces' )
			&& $settings->hasSetting( 'repoConceptBaseUri' )
		) {
			$definitions = [ '' => [
				'database' => $settings->getSetting( 'repoDatabase' ),
				'base-uri' => $settings->getSetting( 'repoConceptBaseUri' ),
				'prefix-mapping' => [ '' => '' ],
				'entity-namespaces' => $settings->getSetting( 'entityNamespaces' ),
			] ];
			unset( $repoSettingsArray[''] );
		}

		foreach ( $repoSettingsArray as $repository => $repositorySettings ) {
			$definitions[$repository] = [
				'database' => $repositorySettings['repoDatabase'],
				'base-uri' => $repositorySettings['baseUri'],
				'entity-namespaces' => $repositorySettings['entityNamespaces'],
				'prefix-mapping' => $repositorySettings['prefixMapping'],
			];
		}

		return new RepositoryDefinitions( $definitions, $entityTypeDefinitions );
	}

	// TODO: current settings (especially (foreign) repositories blob) might be quite confusing
	// Having a "entitySources" or so setting might be better, and would also allow unifying
	// the way these are configured in Repo and in Client parts
	private static function getEntitySourceDefinitionsFromSettings( SettingsArray $settings ) {
		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray( $settings->getSetting( 'entitySources' ) );
		}

		$repoSettingsArray = $settings->hasSetting( 'foreignRepositories' )
			? $settings->getSetting( 'foreignRepositories' )
			: $settings->getSetting( 'repositories' );

		if ( $settings->hasSetting( 'repoDatabase' )
			&& $settings->hasSetting( 'entityNamespaces' )
			&& $settings->hasSetting( 'repoConceptBaseUri' )
		) {
			$localEntityNamespaces = $settings->getSetting( 'entityNamespaces' );
			$localDatabaseName = $settings->getSetting( 'repoDatabase' );
			$localConceptBaseUri = $settings->getSetting( 'repoConceptBaseUri' );
			unset( $repoSettingsArray[''] );
		}

		if ( array_key_exists( '', $repoSettingsArray ) ) {
			$localEntityNamespaces = $repoSettingsArray['']['entityNamespaces'];
			$localDatabaseName = $repoSettingsArray['']['repoDatabase'];
			$localConceptBaseUri = $repoSettingsArray['']['baseUri'];
			unset( $repoSettingsArray[''] );
		}

		$sources = [];

		$localEntityNamespaceSlotData = [];
		foreach ( $localEntityNamespaces as $entityType => $namespaceSlot ) {
			list( $namespaceId, $slot ) = self::splitNamespaceAndSlot( $namespaceSlot );
			$localEntityNamespaceSlotData[$entityType] = [
				'namespaceId' => $namespaceId,
				'slot' => $slot,
			];
		}
		$sources[] = new EntitySource(
			'local',
			$localDatabaseName,
			$localEntityNamespaceSlotData,
			$localConceptBaseUri,
			'wd', // TODO: make configurable
			'', // TODO: make configurable
			''
		);

		foreach ( $repoSettingsArray as $repository => $repositorySettings ) {
			$namespaceSlotData = [];
			foreach ( $repositorySettings['entityNamespaces'] as $entityType => $namespaceSlot ) {
				list( $namespaceId, $slot ) = self::splitNamespaceAndSlot( $namespaceSlot );
				$namespaceSlotData[$entityType] = [
					'namespaceId' => $namespaceId,
					'slot' => $slot,
				];
			}
			$sources[] = new EntitySource(
				$repository,
				$repositorySettings['repoDatabase'],
				$namespaceSlotData,
				$repositorySettings['baseUri'],
				$repository, // TODO: make configurable
				$repository, // TODO: make configurable
				$repository // TODO: this is a "magic" default/assumption
			);
		}

		return new EntitySourceDefinitions( $sources );
	}

	private static function splitNamespaceAndSlot( $namespaceAndSlot ) {
		if ( is_int( $namespaceAndSlot ) ) {
			return [ $namespaceAndSlot, 'main' ];
		}

		if ( !preg_match( '!^(\w*)(/(\w+))?!', $namespaceAndSlot, $m ) ) {
			throw new InvalidArgumentException(
				'Bad namespace/slot specification: an integer namespace index, or a canonical'
				. ' namespace name, or have the form <namespace>/<slot-name>.'
				. ' Found ' . $namespaceAndSlot
			);
		}

		if ( is_numeric( $m[1] ) ) {
			$ns = intval( $m[1] );
		} else {
			$ns = MWNamespace::getCanonicalIndex( strtolower( $m[1] ) );
		}

		if ( !is_int( $ns ) ) {
			throw new InvalidArgumentException(
				'Bad namespace specification: must be either an integer or a canonical'
				. ' namespace name. Found ' . $m[1]
			);
		}

		return [
			$ns,
			$m[3] ?? 'main'
		];
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @param string $reset Flag: Pass "reset" to reset the default instance
	 *
	 * @return self
	 */
	public static function getDefaultInstance( $reset = 'noreset' ) {
		static $instance = null;

		if ( $instance === null || $reset === 'reset' ) {
			$instance = self::newInstance();
		}

		return $instance;
	}

	public function getLogger() {
		return LoggerFactory::getInstance( 'Wikibase' );
	}

	/**
	 * Returns the this client wiki's site object.
	 *
	 * This is taken from the siteGlobalID setting, which defaults
	 * to the wiki's database name.
	 *
	 * If the configured site ID is not found in the sites table, a
	 * new Site object is constructed from the configured ID.
	 *
	 * @throws MWException
	 * @return Site
	 */
	public function getSite() {
		if ( $this->site === null ) {
			$globalId = $this->settings->getSetting( 'siteGlobalID' );
			$localId = $this->settings->getSetting( 'siteLocalID' );

			$this->site = $this->siteLookup->getSite( $globalId );

			// Todo inject me
			$logger = $this->getLogger();

			if ( !$this->site ) {
				$logger->debug(
					'{method}:  Unable to resolve site ID {globalId}!',
					[ 'method' => __METHOD__, 'globalId' => $globalId ]
				);

				$this->site = new MediaWikiSite();
				$this->site->setGlobalId( $globalId );
				$this->site->addLocalId( Site::ID_INTERWIKI, $localId );
				$this->site->addLocalId( Site::ID_EQUIVALENT, $localId );
			}

			if ( !in_array( $localId, $this->site->getLocalIds() ) ) {
				$logger->debug(
					'{method}: The configured local id {localId} does not match any local IDs of site {globalId}: {localIds}',
					[
						'method' => __METHOD__,
						'localId' => $localId,
						'globalId' => $globalId,
						'localIds' => json_encode( $this->site->getLocalIds() )
					]
				);
			}
		}

		return $this->site;
	}

	/**
	 * Returns the site group ID for the group to be used for language links.
	 * This is typically the group the client wiki itself belongs to, but
	 * can be configured to be otherwise using the languageLinkSiteGroup setting.
	 *
	 * @return string
	 */
	public function getLangLinkSiteGroup() {
		$group = $this->settings->getSetting( 'languageLinkSiteGroup' );

		if ( $group === null ) {
			$group = $this->getSiteGroup();
		}

		return $group;
	}

	/**
	 * Gets the site group ID from setting, which if not set then does
	 * lookup in site store.
	 *
	 * @return string
	 */
	private function newSiteGroup() {
		$siteGroup = $this->settings->getSetting( 'siteGroup' );

		if ( !$siteGroup ) {
			$siteId = $this->settings->getSetting( 'siteGlobalID' );

			$site = $this->siteLookup->getSite( $siteId );

			if ( !$site ) {
				return true;
			}

			$siteGroup = $site->getGroup();
		}

		return $siteGroup;
	}

	/**
	 * Get site group ID
	 *
	 * @return string
	 */
	public function getSiteGroup() {
		if ( $this->siteGroup === null ) {
			$this->siteGroup = $this->newSiteGroup();
		}

		return $this->siteGroup;
	}

	/**
	 * Returns a OutputFormatSnakFormatterFactory the provides SnakFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatSnakFormatterFactory
	 */
	private function getSnakFormatterFactory() {
		if ( $this->snakFormatterFactory === null ) {
			$this->snakFormatterFactory = new OutputFormatSnakFormatterFactory(
				$this->dataTypeDefinitions->getSnakFormatterFactoryCallbacks(),
				$this->getValueFormatterFactory(),
				$this->getPropertyDataTypeLookup(),
				$this->getDataTypeFactory()
			);
		}

		return $this->snakFormatterFactory;
	}

	/**
	 * Returns a OutputFormatValueFormatterFactory the provides ValueFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatValueFormatterFactory
	 */
	private function getValueFormatterFactory() {
		if ( $this->valueFormatterFactory === null ) {
			$this->valueFormatterFactory = new OutputFormatValueFormatterFactory(
				$this->dataTypeDefinitions->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				$this->getContentLanguage(),
				$this->getLanguageFallbackChainFactory()
			);
		}

		return $this->valueFormatterFactory;
	}

	/**
	 * @return EntityIdParser
	 */
	private function getRepoItemUriParser() {
		// B/C compatibility, should be removed soon
		// TODO: Move to check repo that has item entity not the default repo
		return new SuffixEntityIdParser(
			$this->getRepositoryDefinitions()->getConceptBaseUris()[''],
			new ItemIdParser()
		);
	}

	/**
	 * @return NamespaceChecker
	 */
	public function getNamespaceChecker() {
		if ( $this->namespaceChecker === null ) {
			$this->namespaceChecker = new NamespaceChecker(
				$this->settings->getSetting( 'excludeNamespaces' ),
				$this->settings->getSetting( 'namespaces' )
			);
		}

		return $this->namespaceChecker;
	}

	/**
	 * @return LangLinkHandler
	 */
	public function getLangLinkHandler() {
		if ( $this->langLinkHandler === null ) {
			$this->langLinkHandler = new LangLinkHandler(
				$this->getLanguageLinkBadgeDisplay(),
				$this->getNamespaceChecker(),
				$this->getStore()->getSiteLinkLookup(),
				$this->getStore()->getEntityLookup(),
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' ),
				$this->getLangLinkSiteGroup()
			);
		}

		return $this->langLinkHandler;
	}

	/**
	 * @return ClientParserOutputDataUpdater
	 */
	public function getParserOutputDataUpdater() {
		if ( $this->parserOutputDataUpdater === null ) {
			$this->parserOutputDataUpdater = new ClientParserOutputDataUpdater(
				$this->getOtherProjectsSidebarGeneratorFactory(),
				$this->getStore()->getSiteLinkLookup(),
				$this->getStore()->getEntityLookup(),
				$this->settings->getSetting( 'siteGlobalID' )
			);
		}

		return $this->parserOutputDataUpdater;
	}

	/**
	 * @return SidebarLinkBadgeDisplay
	 */
	public function getSidebarLinkBadgeDisplay() {
		if ( $this->sidebarLinkBadgeDisplay === null ) {
			$labelDescriptionLookupFactory = $this->getLanguageFallbackLabelDescriptionLookupFactory();
			$badgeClassNames = $this->settings->getSetting( 'badgeClassNames' );
			$lang = $this->getUserLanguage();

			$this->sidebarLinkBadgeDisplay = new SidebarLinkBadgeDisplay(
				$labelDescriptionLookupFactory->newLabelDescriptionLookup( $lang ),
				is_array( $badgeClassNames ) ? $badgeClassNames : [],
				$lang
			);
		}

		return $this->sidebarLinkBadgeDisplay;
	}

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	public function getLanguageLinkBadgeDisplay() {
		return new LanguageLinkBadgeDisplay(
			$this->getSidebarLinkBadgeDisplay()
		);
	}

	/**
	 * @return DeserializerFactory A factory with knowledge about items, properties, and the
	 *  elements they are made of, but no other entity types.
	 */
	public function getBaseDataModelDeserializerFactory() {
		return new DeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser()
		);
	}

	/**
	 * @return InternalDeserializerFactory
	 */
	private function getInternalFormatDeserializerFactory() {
		return new InternalDeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser(),
			$this->getAllTypesEntityDeserializer()
		);
	}

	/**
	 * @return DispatchingDeserializer
	 */
	private function getAllTypesEntityDeserializer() {
		if ( $this->entityDeserializer === null ) {
			$deserializerFactoryCallbacks = $this->getEntityDeserializerFactoryCallbacks();
			$baseDeserializerFactory = $this->getBaseDataModelDeserializerFactory();
			$deserializers = [];

			foreach ( $deserializerFactoryCallbacks as $callback ) {
				$deserializers[] = call_user_func( $callback, $baseDeserializerFactory );
			}

			$this->entityDeserializer = new DispatchingDeserializer( $deserializers );
		}

		return $this->entityDeserializer;
	}

	/**
	 * Returns a deserializer to deserialize statements in both current and legacy serialization.
	 *
	 * @return Deserializer
	 */
	public function getInternalFormatStatementDeserializer() {
		return $this->getInternalFormatDeserializerFactory()->newStatementDeserializer();
	}

	/**
	 * @return callable[]
	 */
	public function getEntityDeserializerFactoryCallbacks() {
		return $this->entityTypeDefinitions->getDeserializerFactoryCallbacks();
	}

	/**
	 * Returns a SerializerFactory creating serializers that generate the most compact serialization.
	 * A factory returned has knowledge about items, properties, and the elements they are made of,
	 * but no other entity types.
	 *
	 * @return SerializerFactory
	 */
	public function getCompactBaseDataModelSerializerFactory() {
		return $this->getWikibaseServices()->getCompactBaseDataModelSerializerFactory();
	}

	/**
	 * Returns an entity serializer that generates the most compact serialization.
	 *
	 * @return Serializer
	 */
	public function getCompactEntitySerializer() {
		return $this->getWikibaseServices()->getCompactEntitySerializer();
	}

	/**
	 * @return DataValueDeserializer
	 */
	private function getDataValueDeserializer() {
		return new DataValueDeserializer( [
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function ( $value ) {
				return isset( $value['id'] )
					? new EntityIdValue( $this->getEntityIdParser()->parse( $value['id'] ) )
					: EntityIdValue::newFromArray( $value );
			},
		] );
	}

	/**
	 * @return OtherProjectsSidebarGeneratorFactory
	 */
	public function getOtherProjectsSidebarGeneratorFactory() {
		return new OtherProjectsSidebarGeneratorFactory(
			$this->settings,
			$this->getStore()->getSiteLinkLookup(),
			$this->siteLookup,
			$this->getStore()->getEntityLookup(),
			$this->getSidebarLinkBadgeDisplay()
		);
	}

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		//TODO: take this from a setting or registry.
		$changeClasses = [
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		];

		return new EntityChangeFactory(
			$this->getEntityDiffer(),
			$this->getEntityIdParser(),
			$changeClasses
		);
	}

	/**
	 * @return EntityDiffer
	 */
	private function getEntityDiffer() {
		$entityDiffer = new EntityDiffer();
		foreach ( $this->entityTypeDefinitions->getEntityDifferStrategyBuilders() as $builder ) {
			$entityDiffer->registerEntityDifferStrategy( call_user_func( $builder ) );
		}
		return $entityDiffer;
	}

	/**
	 * @return ParserFunctionRegistrant
	 */
	public function getParserFunctionRegistrant() {
		return new ParserFunctionRegistrant(
			$this->settings->getSetting( 'allowDataTransclusion' ),
			$this->settings->getSetting( 'allowLocalShortDesc' )
		);
	}

	/**
	 * @return StatementGroupRendererFactory
	 */
	private function getStatementGroupRendererFactory() {
		return new StatementGroupRendererFactory(
			$this->getPropertyLabelResolver(),
			new SnaksFinder(),
			$this->getRestrictedEntityLookup(),
			$this->getDataAccessSnakFormatterFactory(),
			$this->settings->getSetting( 'allowDataAccessInUserLanguage' )
		);
	}

	/**
	 * @return DataAccessSnakFormatterFactory
	 */
	public function getDataAccessSnakFormatterFactory() {
		return new DataAccessSnakFormatterFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory(),
			$this->getPropertyDataTypeLookup(),
			$this->getRepoItemUriParser(),
			$this->getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->settings->getSetting( 'allowDataAccessInUserLanguage' )
		);
	}

	/**
	 * @return Runner
	 */
	public function getPropertyParserFunctionRunner() {
		return new Runner(
			$this->getStatementGroupRendererFactory(),
			$this->getStore()->getSiteLinkLookup(),
			$this->getEntityIdParser(),
			$this->getRestrictedEntityLookup(),
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->settings->getSetting( 'allowArbitraryDataAccess' )
		);
	}

	/**
	 * @return OtherProjectsSitesProvider
	 */
	public function getOtherProjectsSitesProvider() {
		return new CachingOtherProjectsSitesProvider(
			new OtherProjectsSitesGenerator(
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' ),
				$this->settings->getSetting( 'specialSiteLinkGroups' )
			),
			// TODO: Make configurable? Should be similar, maybe identical to sharedCacheType and
			// sharedCacheDuration, but can not reuse these because this here is not shared.
			wfGetMainCache(),
			60 * 60
		);
	}

	/**
	 * @return AffectedPagesFinder
	 */
	private function getAffectedPagesFinder() {
		return new AffectedPagesFinder(
			$this->getStore()->getUsageLookup(),
			new TitleFactory(),
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->getContentLanguage()->getCode()
		);
	}

	/**
	 * @return ChangeHandler
	 */
	public function getChangeHandler() {
		$logger = $this->getLogger();

		$pageUpdater = new WikiPageUpdater(
			JobQueueGroup::singleton(),
			$logger,
			MediaWikiServices::getInstance()->getStatsdDataFactory()
		);

		$pageUpdater->setPurgeCacheBatchSize( $this->settings->getSetting( 'purgeCacheBatchSize' ) );
		$pageUpdater->setRecentChangesBatchSize( $this->settings->getSetting( 'recentChangesBatchSize' ) );

		$changeListTransformer = new ChangeRunCoalescer(
			$this->getStore()->getEntityRevisionLookup(),
			$this->getEntityChangeFactory(),
			$logger,
			$this->settings->getSetting( 'siteGlobalID' )
		);

		return new ChangeHandler(
			$this->getAffectedPagesFinder(),
			new TitleFactory(),
			$pageUpdater,
			$changeListTransformer,
			$this->siteLookup,
			$logger,
			$this->settings->getSetting( 'injectRecentChanges' )
		);
	}

	/**
	 * @return RecentChangeFactory
	 */
	public function getRecentChangeFactory() {
		$repoSite = $this->siteLookup->getSite(
			$this->getRepositoryDefinitions()->getDatabaseNames()['']
		);
		$interwikiPrefixes = ( $repoSite !== null ) ? $repoSite->getInterwikiIds() : [];
		$interwikiPrefix = ( $interwikiPrefixes !== [] ) ? $interwikiPrefixes[0] : null;

		return new RecentChangeFactory(
			$this->getContentLanguage(),
			new SiteLinkCommentCreator(
				$this->getContentLanguage(),
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' )
			),
			( new CentralIdLookupFactory() )->getCentralIdLookup(),
			( $interwikiPrefix !== null ) ?
				new ExternalUserNames( $interwikiPrefix, false ) : null
		);
	}

	public function getWikibaseContentLanguages() {
		if ( $this->wikibaseContentLanguages === null ) {
			$this->wikibaseContentLanguages = WikibaseContentLanguages::getDefaultInstance();
		}

		return $this->wikibaseContentLanguages;
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 *
	 * @return ContentLanguages
	 */
	public function getTermsLanguages() {
		return $this->getWikibaseContentLanguages()->getContentLanguages( 'term' );
	}

	/**
	 * @return RestrictedEntityLookup
	 */
	public function getRestrictedEntityLookup() {
		if ( $this->restrictedEntityLookup === null ) {
			$disabledEntityTypesEntityLookup = new DisabledEntityTypesEntityLookup(
				$this->getEntityLookup(),
				$this->settings->getSetting( 'disabledAccessEntityTypes' )
			);
			$this->restrictedEntityLookup = new RestrictedEntityLookup(
				$disabledEntityTypesEntityLookup,
				$this->settings->getSetting( 'entityAccessLimit' )
			);
		}

		return $this->restrictedEntityLookup;
	}

	/**
	 * @return PropertyOrderProvider
	 */
	public function getPropertyOrderProvider() {
		if ( $this->propertyOrderProvider === null ) {
			$title = Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' );
			$innerProvider = new WikiPagePropertyOrderProvider( $title );

			$url = $this->settings->getSetting( 'propertyOrderUrl' );
			if ( $url !== null ) {
				$innerProvider = new FallbackPropertyOrderProvider(
					$innerProvider,
					new HttpUrlPropertyOrderProvider(
						$url,
						new Http(),
						$this->getLogger()
					)
				);
			}

			$this->propertyOrderProvider = new CachingPropertyOrderProvider(
				$innerProvider,
				wfGetMainCache()
			);
		}

		return $this->propertyOrderProvider;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		return $this->getWikibaseServices()->getEntityNamespaceLookup();
	}

	/**
	 * @param Language $language
	 *
	 * @return LanguageFallbackChain
	 */
	public function getDataAccessLanguageFallbackChain( Language $language ) {
		return $this->getLanguageFallbackChainFactory()->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_ALL
		);
	}

	/**
	 * @return RepositoryDefinitions
	 */
	public function getRepositoryDefinitions() {
		return $this->repositoryDefinitions;
	}

	/**
	 * @fixme this is duplicated in WikibaseRepo...
	 * @return CacheInterface
	 */
	private function getFormatterCache() {
		global $wgSecretKey;

		$cacheType = $this->settings->getSetting( 'sharedCacheType' );
		$cacheSecret = hash( 'sha256', $wgSecretKey );

		// Get out default shared cache wrapped in an in memory cache
		$bagOStuff = ObjectCache::getInstance( $cacheType );
		if ( !$bagOStuff instanceof CachedBagOStuff ) {
			$bagOStuff = new CachedBagOStuff( $bagOStuff );
		}

		$cache = new SimpleCacheWithBagOStuff(
			$bagOStuff,
			'wikibase.client.formatter.',
			$cacheSecret
		);

		$cache->setLogger( $this->getLogger() );

		$cache = new StatsdMissRecordingSimpleCache(
			$cache,
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			'wikibase.client.formatterCache.miss'
		);

		return $cache;
	}

	public function getItemTermIndex() {
		if ( $this->itemTermIndex === null ) {
			$dataAccessSettings = $this->getDataAccessSettings();
			$itemSource = $this->getItemSource( $dataAccessSettings );
			$itemDatabaseName = $dataAccessSettings->useEntitySourceBasedFederation() ?
				$itemSource->getDatabaseName() :
				$this->getRepositoryDefinitions()->getDatabaseNames()[''];
			$itemRepositoryPrefix = '';

			$this->itemTermIndex = new TermSqlIndex(
				$this->getStringNormalizer(),
				$this->getEntityIdComposer(),
				$this->getEntityIdParser(),
				$itemSource,
				$dataAccessSettings,
				$itemDatabaseName,
				$itemRepositoryPrefix
			);
			$this->itemTermIndex->setUseSearchFields( $this->settings->getSetting( 'useTermsTableSearchFields' ) );
			$this->itemTermIndex->setForceWriteSearchFields( $this->settings->getSetting( 'forceWriteTermsTableSearchFields' ) );
		}

		return $this->itemTermIndex;
	}

	private function getItemSource( DataAccessSettings $dataAccessSettings ) {
		if ( $dataAccessSettings->useEntitySourceBasedFederation() ) {
			$itemSource = $this->entitySourceDefinitions->getSourceForEntityType( Item::ENTITY_TYPE );
			if ( $itemSource !== null ) {
				return $itemSource;
			}
		}

		return new UnusableEntitySource();
	}

	/**
	 * @return DescriptionLookup
	 */
	public function getDescriptionLookup() {
		if ( $this->descriptionLookup === null ) {
			// TODO: EntityIdLookup should probably also not come from ClientStore?
			$this->descriptionLookup = new DescriptionLookup( $this->getStore()->getEntityIdLookup(), $this->getItemTermIndex() );
		}
		return $this->descriptionLookup;
	}

	public function getPropertyLabelResolver() {
		if ( $this->propertyLabelResolver === null ) {
			$languageCode = $this->getContentLanguage()->getCode();
			$cacheKeyPrefix = $this->settings->getSetting( 'sharedCacheKeyPrefix' );
			$cacheType = $this->settings->getSetting( 'sharedCacheType' );
			$cacheDuration = $this->settings->getSetting( 'sharedCacheDuration' );

			// Cache key needs to be language specific
			$cacheKey = $cacheKeyPrefix . ':TermPropertyLabelResolver' . '/' . $languageCode;

			$this->propertyLabelResolver = new TermPropertyLabelResolver(
				$languageCode,
				$this->getPropertyTermIndex(),
				ObjectCache::getInstance( $cacheType ),
				$cacheDuration,
				$cacheKey
			);
		}

		return $this->propertyLabelResolver;
	}

	private function getPropertyTermIndex() {
		// TODO: Add special 'optimization' for case item and properties come from the single source to save
		// an instance? Uses of both seem rather exclusive, though, don't they?

		$dataAccessSettings = $this->getDataAccessSettings();
		$propertySource = $this->getPropertySource( $dataAccessSettings );
		$propertyDatabaseName = $dataAccessSettings->useEntitySourceBasedFederation() ?
			$propertySource->getDatabaseName() :
			$this->getRepositoryDefinitions()->getDatabaseNames()[''];
		$propertyRepositoryName = '';

		$index = new TermSqlIndex(
			$this->getStringNormalizer(),
			$this->getEntityIdComposer(),
			$this->getEntityIdParser(),
			$propertySource,
			$dataAccessSettings,
			$propertyDatabaseName,
			$propertyRepositoryName
		);

		// TODO: Are these important? Copied blindly over from DirectSqlStore::getTermIndex
		$index->setUseSearchFields( $this->settings->getSetting( 'useTermsTableSearchFields' ) );
		$index->setForceWriteSearchFields( $this->settings->getSetting( 'forceWriteTermsTableSearchFields' ) );

		return $index;
	}

	private function getPropertySource( DataAccessSettings $dataAccessSettings ) {
		if ( $dataAccessSettings->useEntitySourceBasedFederation() ) {
			$propertySource = $this->entitySourceDefinitions->getSourceForEntityType( Property::ENTITY_TYPE );
			if ( $propertySource !== null ) {
				return $propertySource;
			}
		}

		return new UnusableEntitySource();
	}

}
