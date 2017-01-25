<?php

namespace Wikibase\Repo;

use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use HashBagOStuff;
use Hooks;
use IContextSource;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use MWException;
use RequestContext;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use SiteLookup;
use StubObject;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Client\EntityDataRetrievalServiceFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\EditEntityFactory;
use Wikibase\EntityFactory;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\ItemChange;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DifferenceContentLanguages;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\EntityIdLinkFormatter;
use Wikibase\Lib\EntityIdPlainLinkFormatter;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\MediaWikiNumberLocalizer;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Modules\SettingsValueProvider;
use Wikibase\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Localizer\ChangeOpDeserializationExceptionLocalizer;
use Wikibase\Repo\Search\Elastic\Fields\DescriptionsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\PropertyFieldDefinitions;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\UnitConverter;
use Wikibase\Lib\UnitStorage;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\PropertyInfoBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\Localizer\ChangeOpValidationExceptionLocalizer;
use Wikibase\Repo\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Localizer\GenericExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageParameterFormatter;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;
use Wikibase\Repo\Modules\EntityTypesConfigValueProvider;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;
use Wikibase\Repo\Notifications\HookChangeTransmitter;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\View\WikibaseHtmlSnakFormatterFactory;
use Wikibase\SettingsArray;
use Wikibase\SqlStore;
use Wikibase\Store;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\Store\EntityIdLookup;
use Wikibase\StringNormalizer;
use Wikibase\SummaryFormatter;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ViewFactory;

/**
 * Top level factory for the WikibaseRepo extension.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class WikibaseRepo {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var DataTypeFactory|null
	 */
	private $dataTypeFactory = null;

	/**
	 * @var ValueParserFactory|null
	 */
	private $valueParserFactory = null;

	/**
	 * @var SnakFactory|null
	 */
	private $snakFactory = null;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $propertyDataTypeLookup = null;

	/**
	 * @var LanguageFallbackChainFactory|null
	 */
	private $languageFallbackChainFactory = null;

	/**
	 * @var StatementGuidValidator|null
	 */
	private $statementGuidValidator = null;

	/**
	 * @var Deserializer|null
	 */
	private $entityDeserializer = null;

	/**
	 * @var Serializer[]
	 */
	private $entitySerializers = array();

	/**
	 * @var EntityIdParser|null
	 */
	private $entityIdParser = null;

	/**
	 * @var EntityIdComposer|null
	 */
	private $entityIdComposer = null;

	/**
	 * @var StringNormalizer|null
	 */
	private $stringNormalizer = null;

	/**
	 * @var OutputFormatSnakFormatterFactory|null
	 */
	private $snakFormatterFactory = null;

	/**
	 * @var OutputFormatValueFormatterFactory|null
	 */
	private $valueFormatterFactory = null;

	/**
	 * @var SummaryFormatter|null
	 */
	private $summaryFormatter = null;

	/**
	 * @var ExceptionLocalizer|null
	 */
	private $exceptionLocalizer = null;

	/**
	 * @var SiteLookup|null
	 */
	private $siteLookup = null;

	/**
	 * @var Store|null
	 */
	private $store = null;

	/**
	 * @var EntityNamespaceLookup|null
	 */
	private $entityNamespaceLookup = null;

	/**
	 * @var TermLookup|null
	 */
	private $termLookup = null;

	/**
	 * @var ContentLanguages|null
	 */
	private $monolingualTextLanguages = null;

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
	 * @var ValueSnakRdfBuilderFactory
	 */
	private $valueSnakRdfBuilderFactory;

	/**
	 * @var RdfVocabulary
	 */
	private $rdfVocabulary;

	/**
	 * @var CachingCommonsMediaFileNameLookup|null
	 */
	private $cachingCommonsMediaFileNameLookup = null;

	/**
	 * @var EntityDataRetrievalServiceFactory|null
	 */
	private $entityDataRetrievalServiceFactory = null;

	/**
	 * @var EntityRdfBuilderFactory|null
	 */
	private $entityRdfBuilderFactory = null;

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @throws MWException
	 * @return self
	 */
	private static function newInstance() {
		global $wgWBRepoDataTypes, $wgWBRepoSettings;

		if ( !is_array( $wgWBRepoDataTypes ) ) {
			throw new MWException( '$wgWBRepoDataTypes must be array. '
				. 'Maybe you forgot to require Wikibase.php in your LocalSettings.php?' );
		}

		$dataTypeDefinitions = $wgWBRepoDataTypes;
		Hooks::run( 'WikibaseRepoDataTypes', array( &$dataTypeDefinitions ) );

		$entityTypeDefinitions = self::getDefaultEntityTypes();
		Hooks::run( 'WikibaseRepoEntityTypes', array( &$entityTypeDefinitions ) );

		$settings = new SettingsArray( $wgWBRepoSettings );
		$settings->setSetting( 'entityNamespaces', self::buildEntityNamespaceConfigurations() );

		$repositoryDefinitions = self::getRepositoryDefinitionsFromSettings( $settings );

		$dataRetrievalServices = null;

		// If client functionality is enabled, use it to enable federation.
		if ( defined( 'WBC_VERSION' ) ) {
			$dataRetrievalServices = WikibaseClient::getDefaultInstance()->getEntityDataRetrievalServiceFactory();
			$repositoryDefinitions = WikibaseClient::getDefaultInstance()->getRepositoryDefinitions();
		}

		return new self(
			$settings,
			new DataTypeDefinitions(
				$dataTypeDefinitions,
				$settings->getSetting( 'disabledDataTypes' )
			),
			new EntityTypeDefinitions( $entityTypeDefinitions ),
			$repositoryDefinitions,
			$dataRetrievalServices
		);
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return RepositoryDefinitions
	 */
	private static function getRepositoryDefinitionsFromSettings( SettingsArray $settings ) {
		return new RepositoryDefinitions( [ '' => [
			'database' => $settings->getSetting( 'changesDatabase' ),
			'base-uri' => $settings->getSetting( 'conceptBaseUri' ),
			'prefix-mapping' => [ '' => '' ],
			'entity-types' => array_keys( $settings->getSetting( 'entityNamespaces' ) ),
		] ] );
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @return self
	 */
	public static function getDefaultInstance() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = self::newInstance();
		}

		return $instance;
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getDataTypeValidatorFactory() instead!
	 *
	 * @return ValidatorBuilders
	 */
	public static function getDefaultValidatorBuilders() {
		static $builders;

		if ( $builders === null ) {
			$wikibaseRepo = self::getDefaultInstance();
			$builders = $wikibaseRepo->newValidatorBuilders();
		}

		return $builders;
	}

	/**
	 * Returns a low level factory object for creating validators for well known data types.
	 *
	 * @warning This is for use with getDefaultValidatorBuilders() during bootstrap only!
	 * Program logic should use WikibaseRepo::getDataTypeValidatorFactory() instead!
	 *
	 * @return ValidatorBuilders
	 */
	public function newValidatorBuilders() {
		$urlSchemes = $this->settings->getSetting( 'urlSchemes' );

		return new ValidatorBuilders(
			$this->getEntityLookup(),
			$this->getEntityIdParser(),
			$urlSchemes,
			$this->getVocabularyBaseUri(),
			$this->getMonolingualTextLanguages(),
			$this->getCachingCommonsMediaFileNameLookup(),
			$this->repositoryDefinitions->getEntityTypesPerRepository(),
			new MediaWikiPageNameNormalizer(),
			$this->settings->getSetting( 'geoShapeStorageApiEndpointUrl' )
		);
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders() {
		static $builders;

		if ( $builders === null ) {
			$wikibaseRepo = self::getDefaultInstance();
			$builders = $wikibaseRepo->newWikibaseValueFormatterBuilders();
		}

		return $builders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders() {
		return new WikibaseValueFormatterBuilders(
			$this->getContentLanguage(),
			new FormatterLabelDescriptionLookupFactory( $this->getTermLookup() ),
			$this->getLanguageNameLookup(),
			$this->getLocalItemUriParser(),
			$this->getSettings()->getSetting( 'geoShapeStorageFrontendUrl' ),
			$this->getEntityTitleLookup()
		);
	}

	/**
	 * @return LanguageNameLookup
	 */
	public function getLanguageNameLookup() {
		return new LanguageNameLookup( $this->getUserLanguage()->getCode() );
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
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
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
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

	/**
	 * FIXME: Optional $entityDataRetrievalServiceFactory makes it possible to access
	 * entities from foreign repositories from Repo component but they also introduce the optional
	 * dependency on the Client component. Such dependency is bad and in the long run it should be removed
	 * by making EntityDataRetrievalServiceFactory implementation provided to WikibaseRepo not be
	 * bound to WikibaseClient.
	 *
	 * @param SettingsArray $settings
	 * @param DataTypeDefinitions $dataTypeDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param RepositoryDefinitions $repositoryDefinitions
	 * @param EntityDataRetrievalServiceFactory|null $entityDataRetrievalServiceFactory optional factory
	 *        of entity data retrieval services that will be used by the Repo instead of it creating
	 *        instances of those services itself.
	 *        This factory could be provided in order to allow Repo make use of Dispatching services
	 *        and access data of entities from foreign repositories.
	 */
	public function __construct(
		SettingsArray $settings,
		DataTypeDefinitions $dataTypeDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		RepositoryDefinitions $repositoryDefinitions,
		EntityDataRetrievalServiceFactory $entityDataRetrievalServiceFactory = null
	) {
		$this->settings = $settings;
		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->repositoryDefinitions = $repositoryDefinitions;
		$this->entityDataRetrievalServiceFactory = $entityDataRetrievalServiceFactory;
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	private function getContentLanguage() {
		global $wgContLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgContLang in the constructor, because it may still be null
		// when WikibaseRepo is initialized. In particular, the language object may not yet
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
	public function getUserLanguage() {
		global $wgLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgLang in the constructor, because it may still be null
		// when WikibaseRepo is initialized. In particular, the language object may not yet
		// be there when the SetupAfterCache hook is run during bootstrapping.

		if ( !$wgLang ) {
			throw new MWException( 'Premature access: $wgLang is not yet initialized!' );
		}

		StubObject::unstub( $wgLang );
		return $wgLang;
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
	 * @return array[]
	 */
	private static function getDefaultEntityTypes() {
		$baseEntityTypes = require __DIR__ . '/../../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/../WikibaseRepo.entitytypes.php';

		return array_merge_recursive( $baseEntityTypes, $repoEntityTypes );
	}

	/**
	 * @return ValueParserFactory
	 */
	public function getValueParserFactory() {
		global $wgValueParsers;

		if ( $this->valueParserFactory === null ) {
			$callbacks = $this->dataTypeDefinitions->getParserFactoryCallbacks();

			// For backwards-compatibility, also register parsers under legacy names.
			$callbacks = array_merge( $wgValueParsers, $callbacks );

			$this->valueParserFactory = new ValueParserFactory( $callbacks );
		}

		return $this->valueParserFactory;
	}

	/**
	 * @return DataValueFactory
	 */
	public function getDataValueFactory() {
		return new DataValueFactory( $this->getDataValueDeserializer() );
	}

	/**
	 * @return EntityContentFactory
	 */
	public function getEntityContentFactory() {
		return new EntityContentFactory(
			$this->getContentModelMappings(),
			$this->entityTypeDefinitions->getContentHandlerFactoryCallbacks(),
			MediaWikiServices::getInstance()->getInterwikiLookup()
		);
	}

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		//TODO: take this from a setting or registry.
		$changeClasses = array(
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		);

		return new EntityChangeFactory(
			$this->getEntityDiffer(),
			$changeClasses
		);
	}

	/**
	 * @return EntityDiffer
	 */
	public function getEntityDiffer() {
		$strategieBuilders = $this->entityTypeDefinitions->getEntityDifferStrategyBuilders();
		$entityDiffer = new EntityDiffer();
		foreach ( $strategieBuilders as $strategyBuilder ) {
			$entityDiffer->registerEntityDifferStrategy( call_user_func( $strategyBuilder ) );
		}
		return $entityDiffer;
	}

	/**
	 * @return EntityPatcher
	 */
	public function getEntityPatcher() {
		$strategieBuilders = $this->entityTypeDefinitions->getEntityPatcherStrategyBuilders();
		$entityPatcher = new EntityPatcher();
		foreach ( $strategieBuilders as $strategyBuilder ) {
			$entityPatcher->registerEntityPatcherStrategy( call_user_func( $strategyBuilder ) );
		}
		return $entityPatcher;
	}

	/**
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher() {
		return $this->getStore()->getEntityStoreWatcher();
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	public function getEntityTitleLookup() {
		return $this->getEntityContentFactory();
	}

	/**
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		return $this->getEntityContentFactory();
	}

	/**
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $uncached = '' ) {
		return $this->getStore()->getEntityRevisionLookup( $uncached );
	}

	/**
	 * @param User $user
	 * @param IContextSource $context
	 *
	 * @return RedirectCreationInteractor
	 */
	public function newRedirectCreationInteractor( User $user, IContextSource $context ) {
		return new RedirectCreationInteractor(
			$this->getEntityRevisionLookup( 'uncached' ),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getSummaryFormatter(),
			$user,
			$this->newEditFilterHookRunner( $context ),
			$this->getStore()->getEntityRedirectLookup(),
			$this->getEntityTitleLookup()
		);
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return EditFilterHookRunner
	 */
	private function newEditFilterHookRunner( IContextSource $context ) {
		return new EditFilterHookRunner(
			$this->getEntityNamespaceLookup(),
			$this->getEntityTitleLookup(),
			$this->getEntityContentFactory(),
			$context
		);
	}

	/**
	 * @param string $displayLanguageCode
	 *
	 * @return TermIndexSearchInteractor
	 */
	public function newTermSearchInteractor( $displayLanguageCode ) {
		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			return $this->entityDataRetrievalServiceFactory->getTermSearchInteractorFactory()->newInteractor(
				$displayLanguageCode
			);
		}

		return new TermIndexSearchInteractor(
			$this->getStore()->getTermIndex(),
			$this->getLanguageFallbackChainFactory(),
			$this->getPrefetchingTermLookup(),
			$displayLanguageCode
		);
	}

	/**
	 * @return EntityStore
	 */
	public function getEntityStore() {
		return $this->getStore()->getEntityStore();
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
				$retrievingLookup
			);
		}

		return $this->propertyDataTypeLookup;
	}

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		if ( $this->stringNormalizer === null ) {
			$this->stringNormalizer = new StringNormalizer();
		}

		return $this->stringNormalizer;
	}

	/**
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $uncached = '' ) {
		return $this->getStore()->getEntityLookup( $uncached );
	}

	/**
	 * @return SnakFactory
	 */
	public function getSnakFactory() {
		if ( $this->snakFactory === null ) {
			$this->snakFactory = new SnakFactory(
				$this->getPropertyDataTypeLookup(),
				$this->getDataTypeFactory(),
				$this->getDataValueFactory()
			);
		}

		return $this->snakFactory;
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
	 * @return StatementGuidParser
	 */
	public function getStatementGuidParser() {
		return new StatementGuidParser( $this->getEntityIdParser() );
	}

	/**
	 * @return ChangeOpFactoryProvider
	 */
	public function getChangeOpFactoryProvider() {
		return new ChangeOpFactoryProvider(
			$this->getEntityConstraintProvider(),
			new GuidGenerator(),
			$this->getStatementGuidValidator(),
			$this->getStatementGuidParser(),
			$this->getSnakValidator(),
			$this->getTermValidatorFactory(),
			$this->getSiteLookup(),
			array_keys( $this->settings->getSetting( 'badgeItems' ) )
		);
	}

	/**
	 * @return SnakValidator
	 */
	public function getSnakValidator() {
		return new SnakValidator(
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory(),
			$this->getDataTypeValidatorFactory()
		);
	}

	public function getSiteLinkBadgeChangeOpSerializationValidator() {
		return new SiteLinkBadgeChangeOpSerializationValidator(
			$this->getEntityTitleLookup(),
			array_keys( $this->settings->getSetting( 'badgeItems' ) )
		);
	}

	/**
	 * @return EntityChangeOpProvider
	 */
	public function getEntityChangeOpProvider() {
		return new EntityChangeOpProvider( $this->entityTypeDefinitions->getChangeOpDeserializerCallbacks() );
	}

	/**
	 * TODO: this should be probably cached?
	 *
	 * @return ChangeOpDeserializerFactory
	 */
	public function getChangeOpDeserializerFactory() {
		$changeOpFactoryProvider = $this->getChangeOpFactoryProvider();

		return new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( $this->getTermsLanguages() ),
			$this->getSiteLinkBadgeChangeOpSerializationValidator(),
			$this->getExternalFormatStatementDeserializer(),
			new SiteLinkTargetProvider(
				$this->getSiteLookup(),
				$this->settings->getSetting( 'specialSiteLinkGroups' )
			),
			$this->getEntityIdParser(),
			$this->getStringNormalizer(),
			$this->settings->getSetting( 'siteLinkGroups' )
		);
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
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
	 * @return StatementGuidValidator
	 */
	public function getStatementGuidValidator() {
		if ( $this->statementGuidValidator === null ) {
			$this->statementGuidValidator = new StatementGuidValidator( $this->getEntityIdParser() );
		}

		return $this->statementGuidValidator;
	}

	/**
	 * @return SettingsArray
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * @return Store
	 */
	public function getStore() {
		if ( $this->store === null ) {
			$this->store = new SqlStore(
				$this->getEntityChangeFactory(),
				$this->getEntityContentDataCodec(),
				$this->getEntityIdParser(),
				$this->getEntityIdComposer(),
				$this->getEntityIdLookup(),
				$this->getEntityTitleLookup(),
				$this->getEntityNamespaceLookup(),
				$this->entityDataRetrievalServiceFactory
			);
		}

		return $this->store;
	}

	/**
	 * Returns a OutputFormatSnakFormatterFactory the provides SnakFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatSnakFormatterFactory
	 */
	public function getSnakFormatterFactory() {
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
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getPrefetchingTermLookup();
	}

	/**
	 * @return TermLookup
	 */
	public function getTermLookup() {
		return $this->getPrefetchingTermLookup();
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	public function getPrefetchingTermLookup() {
		if ( !$this->termLookup ) {
			$this->termLookup = $this->newPrefetchingTermLookup();
		}

		return $this->termLookup;
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	private function newPrefetchingTermLookup() {
		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			return $this->entityDataRetrievalServiceFactory->getTermBuffer();
		}
		return new BufferingTermLookup(
			$this->getStore()->getTermIndex(),
			1000 // @todo: configure buffer size
		);
	}

	/**
	 * @return EntityIdParser
	 */
	private function getLocalItemUriParser() {
		return new SuffixEntityIdParser(
			$this->getVocabularyBaseUri(),
			new ItemIdParser()
		);
	}

	/**
	 * @return string
	 */
	private function getVocabularyBaseUri() {
		//@todo: We currently use the local repo concept URI here. This should be configurable,
		// to e.g. allow 3rd parties to use Wikidata as their vocabulary repo.
		return $this->settings->getSetting( 'conceptBaseUri' );
	}

	/**
	 * Returns a OutputFormatValueFormatterFactory the provides ValueFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatValueFormatterFactory
	 */
	public function getValueFormatterFactory() {
		if ( $this->valueFormatterFactory === null ) {
			$this->valueFormatterFactory = $this->newValueFormatterFactory();
		}

		return $this->valueFormatterFactory;
	}

	/**
	 * @return OutputFormatValueFormatterFactory
	 */
	private function newValueFormatterFactory() {
		return new OutputFormatValueFormatterFactory(
			$this->dataTypeDefinitions->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			$this->getContentLanguage(),
			new LanguageFallbackChainFactory()
		);
	}

	/**
	 * @return ValueSnakRdfBuilderFactory
	 */
	public function getValueSnakRdfBuilderFactory() {
		if ( $this->valueSnakRdfBuilderFactory === null ) {
			$this->valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				$this->dataTypeDefinitions->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
			);
		}

		return $this->valueSnakRdfBuilderFactory;
	}

	/**
	 * @return RdfVocabulary
	 */
	public function getRdfVocabulary() {
		global $wgDummyLanguageCodes;

		if ( $this->rdfVocabulary === null ) {
			$languageCodes = array_merge(
				$wgDummyLanguageCodes,
				$this->settings->getSetting( 'canonicalLanguageCodes' )
			);

			$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData' );

			$this->rdfVocabulary = new RdfVocabulary(
				$this->getVocabularyBaseUri(),
				$entityDataTitle->getCanonicalURL() . '/',
				$languageCodes,
				$this->dataTypeDefinitions->getRdfTypeUris(),
				$this->settings->getSetting( 'pagePropertiesRdf' ) ?: []
			);
		}

		return $this->rdfVocabulary;
	}

	/**
	 * @return ExceptionLocalizer
	 */
	public function getExceptionLocalizer() {
		if ( $this->exceptionLocalizer === null ) {
			$formatter = $this->getMessageParameterFormatter();
			$localizers = $this->getExceptionLocalizers( $formatter );

			$this->exceptionLocalizer = new DispatchingExceptionLocalizer( $localizers, $formatter );
		}

		return $this->exceptionLocalizer;
	}

	/**
	 * @param ValueFormatter $formatter
	 *
	 * @return ExceptionLocalizer[]
	 */
	private function getExceptionLocalizers( ValueFormatter $formatter ) {
		return array(
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'ChangeOpValidationException' => new ChangeOpValidationExceptionLocalizer( $formatter ),
			'ChangeOpDeserializationException' => new ChangeOpDeserializationExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer()
		);
	}

	/**
	 * @return SummaryFormatter
	 */
	public function getSummaryFormatter() {
		if ( $this->summaryFormatter === null ) {
			$this->summaryFormatter = $this->newSummaryFormatter();
		}

		return $this->summaryFormatter;
	}

	/**
	 * @return SummaryFormatter
	 */
	private function newSummaryFormatter() {
		// This needs to use an EntityIdPlainLinkFormatter as we want to mangle
		// the links created in LinkBeginHookHandler afterwards (the links must not
		// contain a display text: [[Item:Q1]] is fine but [[Item:Q1|Q1]] isn't).
		$idFormatter = new EntityIdPlainLinkFormatter( $this->getEntityContentFactory() );

		// Create a new ValueFormatterFactory, and override the formatter for entity IDs.
		$valueFormatterFactory = $this->newValueFormatterFactory();

		// Iterate through all defined entity types
		foreach ( $this->entityTypeDefinitions->getEntityTypes() as $entityType ) {
			$valueFormatterFactory->setFormatterFactoryCallback(
				"PT:wikibase-$entityType",
				function ( $format, FormatterOptions $options ) use ( $idFormatter ) {
					if ( $format === SnakFormatter::FORMAT_PLAIN ) {
						return new EntityIdValueFormatter( $idFormatter );
					} else {
						return null;
					}
				}
			);
		}

		// Create a new SnakFormatterFactory based on the specialized ValueFormatterFactory.
		$snakFormatterFactory = new OutputFormatSnakFormatterFactory(
			array(), // XXX: do we want $this->dataTypeDefinitions->getSnakFormatterFactoryCallbacks()
			$valueFormatterFactory,
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory()
		);

		$options = new FormatterOptions();
		$snakFormatter = $snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$options
		);
		$valueFormatter = $valueFormatterFactory->getValueFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$options
		);

		$formatter = new SummaryFormatter(
			$idFormatter,
			$valueFormatter,
			$snakFormatter,
			$this->getContentLanguage(),
			$this->getEntityIdParser()
		);

		return $formatter;
	}

	/**
	 * @return EntityPermissionChecker
	 */
	public function getEntityPermissionChecker() {
		return $this->getEntityContentFactory();
	}

	/**
	 * @return TermValidatorFactory
	 */
	public function getTermValidatorFactory() {
		$constraints = $this->settings->getSetting( 'multilang-limits' );
		$maxLength = $constraints['length'];

		$languages = $this->getTermsLanguages()->getLanguages();

		return new TermValidatorFactory(
			$maxLength,
			$languages,
			$this->getEntityIdParser(),
			$this->getLabelDescriptionDuplicateDetector()
		);
	}

	/**
	 * @return EntityConstraintProvider
	 */
	public function getEntityConstraintProvider() {
		return new EntityConstraintProvider(
			$this->getLabelDescriptionDuplicateDetector(),
			$this->getStore()->getSiteLinkConflictLookup()
		);
	}

	/**
	 * @return ValidatorErrorLocalizer
	 */
	public function getValidatorErrorLocalizer() {
		return new ValidatorErrorLocalizer( $this->getMessageParameterFormatter() );
	}

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	public function getLabelDescriptionDuplicateDetector() {
		return new LabelDescriptionDuplicateDetector( $this->getStore()->getLabelConflictFinder() );
	}

	/**
	 * @return SiteLookup
	 */
	public function getSiteLookup() {
		if ( $this->siteLookup === null ) {
			$this->siteLookup = MediaWikiServices::getInstance()->getSiteLookup();
		}

		return $this->siteLookup;
	}

	/**
	 * Returns a ValueFormatter suitable for converting message parameters to wikitext.
	 * The formatter is most likely implemented to dispatch to different formatters internally,
	 * based on the type of the parameter.
	 *
	 * @return ValueFormatter
	 */
	private function getMessageParameterFormatter() {
		$formatterOptions = new FormatterOptions();
		$valueFormatterFactory = $this->getValueFormatterFactory();

		return new MessageParameterFormatter(
			$valueFormatterFactory->getValueFormatter( SnakFormatter::FORMAT_WIKI, $formatterOptions ),
			new EntityIdLinkFormatter( $this->getEntityTitleLookup() ),
			$this->getSiteLookup(),
			$this->getUserLanguage()
		);
	}

	/**
	 * @return ChangeNotifier
	 */
	public function getChangeNotifier() {
		$transmitters = [
			new HookChangeTransmitter( 'WikibaseChangeNotification' ),
		];

		if ( $this->settings->getSetting( 'useChangesTable' ) ) {
			$transmitters[] = new DatabaseChangeTransmitter( $this->getStore()->getChangeStore() );
		}

		return new ChangeNotifier( $this->getEntityChangeFactory(), $transmitters );
	}

	/**
	 * Get the mapping of entity types => content models
	 *
	 * @return array
	 */
	public function getContentModelMappings() {
		$map = $this->entityTypeDefinitions->getContentModelIds();

		Hooks::run( 'WikibaseContentModelMapping', array( &$map ) );

		return $map;
	}

	/**
	 * @return EntityFactory
	 */
	public function getEntityFactory() {
		$instantiators = $this->entityTypeDefinitions->getEntityFactoryCallbacks();

		return new EntityFactory( $instantiators );
	}

	/**
	 * @return string[] List of entity type identifiers (typically "item" and "property")
	 *  that are configured in WikibaseRepo.entitytypes.php and enabled via the
	 *  $wgWBRepoSettings['entityNamespaces'] setting. Optionally the list also contains
	 *  entity types from the configured foreign repositories.
	 */
	public function getEnabledEntityTypes() {
		return $this->repositoryDefinitions->getAllEntityTypes();
	}

	/**
	 * @return string[] List of entity type identifiers (typically "item" and "property")
	 *  that are configured in WikibaseRepo.entitytypes.php and enabled via the
	 *  $wgWBRepoSettings['entityNamespaces'] setting.
	 */
	public function getLocalEntityTypes() {
		return array_keys( $this->getEntityNamespaces() );
	}

	/**
	 * @return EntityContentDataCodec
	 */
	public function getEntityContentDataCodec() {
		return new EntityContentDataCodec(
			$this->getEntityIdParser(),
			$this->getEntitySerializer(),
			$this->getInternalFormatEntityDeserializer(),
			$this->settings->getSetting( 'maxSerializedEntitySize' ) * 1024
		);
	}

	/**
	 * @return DeserializerFactory
	 */
	public function getExternalFormatDeserializerFactory() {
		return new DeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser()
		);
	}

	/**
	 * @return InternalDeserializerFactory
	 */
	public function getInternalFormatDeserializerFactory() {
		return new InternalDeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser(),
			$this->getExternalFormatEntityDeserializer()
		);
	}

	/**
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return SerializerFactory
	 */
	public function getSerializerFactory( $options = SerializerFactory::OPTION_DEFAULT ) {
		return new SerializerFactory( new DataValueSerializer(), $options );
	}

	/**
	 * Returns a deserializer to deserialize entities in current serialization only.
	 *
	 * @return Deserializer
	 */
	public function getExternalFormatEntityDeserializer() {
		if ( $this->entityDeserializer === null ) {
			$deserializerFactoryCallbacks = $this->entityTypeDefinitions->getDeserializerFactoryCallbacks();
			$deserializerFactory = $this->getExternalFormatDeserializerFactory();
			$deserializers = array();

			foreach ( $deserializerFactoryCallbacks as $callback ) {
				$deserializers[] = call_user_func( $callback, $deserializerFactory );
			}

			$this->entityDeserializer = new DispatchingDeserializer( $deserializers );
		}

		return $this->entityDeserializer;
	}

	/**
	 * Returns a deserializer to deserialize entities in both current and legacy serialization.
	 *
	 * @return Deserializer
	 */
	public function getInternalFormatEntityDeserializer() {
		return $this->getInternalFormatDeserializerFactory()->newEntityDeserializer();
	}

	/**
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return Serializer
	 */
	public function getEntitySerializer( $options = SerializerFactory::OPTION_DEFAULT ) {
		if ( !isset( $this->entitySerializers[$options] ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$serializerFactory = $this->getSerializerFactory( $options );
			$serializers = array();

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $serializerFactory );
			}

			$this->entitySerializers[$options] = new DispatchingSerializer( $serializers );
		}

		return $this->entitySerializers[$options];
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
	 * Returns a deserializer to deserialize statements in current serialization only.
	 *
	 * @return Deserializer
	 */
	public function getExternalFormatStatementDeserializer() {
		return $this->getExternalFormatDeserializerFactory()->newStatementDeserializer();
	}

	/**
	 * @return Serializer
	 */
	public function getStatementSerializer() {
		return $this->getSerializerFactory()->newStatementSerializer();
	}

	/**
	 * @return Deserializer
	 */
	private function getDataValueDeserializer() {
		return new DataValueDeserializer( array(
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function( $value ) {
				return isset( $value['id'] )
					? new EntityIdValue( $this->getEntityIdParser()->parse( $value['id'] ) )
					: EntityIdValue::newFromArray( $value );
			},
		) );
	}

	/**
	 * @return ItemHandler
	 */
	public function newItemHandler() {
		$entityPerPage = $this->getStore()->newEntityPerPage();
		$termIndex = $this->getStore()->getTermIndex();
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = $this->getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$siteLinkStore = $this->getStore()->newSiteLinkStore();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		$handler = new ItemHandler(
			$entityPerPage,
			$termIndex,
			$codec,
			$constraintProvider,
			$errorLocalizer,
			$this->getEntityIdParser(),
			$siteLinkStore,
			$this->getEntityIdLookup(),
			$this->getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->getItemFieldDefinitions(),
			$legacyFormatDetector
		);

		return $handler;
	}

	/**
	 * @return LabelsProviderFieldDefinitions
	 */
	public function getLabelProviderDefinitions() {
		return new LabelsProviderFieldDefinitions( $this->getTermsLanguages()->getLanguages() );
	}

	/**
	 * @return DescriptionsProviderFieldDefinitions
	 */
	public function getDescriptionProviderDefinitions() {
		return new DescriptionsProviderFieldDefinitions( $this->getTermsLanguages()
			->getLanguages() );
	}

	/**
	 * @return ItemFieldDefinitions
	 */
	private function getItemFieldDefinitions() {
		return new ItemFieldDefinitions(
			$this->getLabelProviderDefinitions(), $this->getDescriptionProviderDefinitions()
		);
	}

	/**
	 * @return PropertyFieldDefinitions
	 */
	private function getPropertyFieldDefinitions() {
		return new PropertyFieldDefinitions(
			$this->getLabelProviderDefinitions(), $this->getDescriptionProviderDefinitions()
		);
	}

	/**
	 * @return PropertyHandler
	 */
	public function newPropertyHandler() {
		$entityPerPage = $this->getStore()->newEntityPerPage();
		$termIndex = $this->getStore()->getTermIndex();
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = $this->getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$propertyInfoStore = $this->getStore()->getPropertyInfoStore();
		$propertyInfoBuilder = $this->newPropertyInfoBuilder();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		$handler = new PropertyHandler(
			$entityPerPage,
			$termIndex,
			$codec,
			$constraintProvider,
			$errorLocalizer,
			$this->getEntityIdParser(),
			$this->getEntityIdLookup(),
			$this->getLanguageFallbackLabelDescriptionLookupFactory(),
			$propertyInfoStore,
			$propertyInfoBuilder,
			$this->getPropertyFieldDefinitions(),
			$legacyFormatDetector
		);

		return $handler;
	}

	/**
	 * @return PropertyInfoBuilder
	 */
	public function newPropertyInfoBuilder() {
		$formatterUrlProperty = $this->settings->getSetting( 'formatterUrlProperty' );

		if ( $formatterUrlProperty !== null ) {
			$formatterUrlProperty = new PropertyId( $formatterUrlProperty );
		}

		return new PropertyInfoBuilder( $formatterUrlProperty );
	}

	private function getLegacyFormatDetectorCallback() {
		$transformOnExport = $this->settings->getSetting( 'transformLegacyFormatOnExport' );

		if ( !$transformOnExport ) {
			return null;
		}

		/**
		 * Detects blobs that may be using a legacy serialization format.
		 * WikibaseRepo uses this for the $legacyExportFormatDetector parameter
		 * when constructing EntityHandlers.
		 *
		 * @see WikibaseRepo::newItemHandler
		 * @see WikibaseRepo::newPropertyHandler
		 * @see EntityHandler::__construct
		 *
		 * @note: False positives (detecting a legacy format when really no legacy format was used)
		 * are acceptable, false negatives (failing to detect a legacy format when one was used)
		 * are not acceptable.
		 *
		 * @param string $blob
		 * @param string $format
		 *
		 * @return bool True if $blob seems to be using a legacy serialization format.
		 */
		return function( $blob, $format ) {
			// The legacy serialization uses something like "entity":["item",21] or
			// even "entity":"p21" for the entity ID.
			return preg_match( '/"entity"\s*:/', $blob ) > 0;
		};
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ApiHelperFactory
	 */
	public function getApiHelperFactory( IContextSource $context ) {
		$serializerOptions = SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH
			+ SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH;

		return new ApiHelperFactory(
			$this->getEntityTitleLookup(),
			$this->getExceptionLocalizer(),
			$this->getPropertyDataTypeLookup(),
			$this->getSiteLookup(),
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup( 'uncached' ),
			$this->newEditEntityFactory( $context ),
			$this->getSerializerFactory( $serializerOptions ),
			$this->getEntitySerializer( $serializerOptions ),
			$this->getEntityIdParser(),
			$this->getStore()->newSiteLinkStore(),
			$this->getEntityFactory(),
			$this->getEntityStore()
		);
	}

	/**
	 * @param IContextSource|null $context
	 *
	 * @return EditEntityFactory
	 */
	public function newEditEntityFactory( IContextSource $context = null ) {
		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		return new EditEntityFactory(
			$this->getEntityTitleLookup(),
			$this->getEntityRevisionLookup( 'uncached' ),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getEntityDiffer(),
			$this->getEntityPatcher(),
			$this->newEditFilterHookRunner( $context ),
			$context
		);
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ItemMergeInteractor
	 */
	public function newItemMergeInteractor( IContextSource $context ) {
		$user = $context->getUser();

		return new ItemMergeInteractor(
			$this->getChangeOpFactoryProvider()->getMergeChangeOpFactory(),
			$this->getEntityRevisionLookup( 'uncached' ),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getSummaryFormatter(),
			$user,
			$this->newRedirectCreationInteractor( $user, $context ),
			$this->getEntityTitleLookup()
		);
	}

	/**
	 * @throws MWException in case of a misconfiguration
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	public static function buildEntityNamespaceConfigurations() {
		global $wgWBRepoSettings;

		if ( empty( $wgWBRepoSettings['entityNamespaces'] ) ) {
			throw new MWException( 'Wikibase: Incomplete configuration: '
				. '$wgWBRepoSettings[\'entityNamespaces\'] has to be set to an '
				. 'array mapping entity types to namespace IDs. '
				. 'See Wikibase.example.php for details and examples.' );
		}

		$namespaces = $wgWBRepoSettings['entityNamespaces'];
		Hooks::run( 'WikibaseEntityNamespaces', array( &$namespaces ) );
		return $namespaces;
	}

	/**
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	public function getEntityNamespaces() {
		return $this->settings->getSetting( 'entityNamespaces' );
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		if ( $this->entityNamespaceLookup === null ) {
			$this->entityNamespaceLookup = new EntityNamespaceLookup(
				$this->getEntityNamespaces()
			);
		}

		return $this->entityNamespaceLookup;
	}

	/**
	 * @return EntityIdHtmlLinkFormatterFactory
	 */
	public function getEntityIdHtmlLinkFormatterFactory() {
		return new EntityIdHtmlLinkFormatterFactory(
			$this->getEntityTitleLookup(),
			$this->getLanguageNameLookup()
		);
	}

	/**
	 * @return EntityParserOutputGeneratorFactory
	 */
	public function getEntityParserOutputGeneratorFactory() {
		$entityDataFormatProvider = new EntityDataFormatProvider();
		$formats = $this->settings->getSetting( 'entityDataFormats' );
		$entityDataFormatProvider->setFormatWhiteList( $formats );

		return new EntityParserOutputGeneratorFactory(
			new DispatchingEntityViewFactory( $this->entityTypeDefinitions->getViewFactoryCallbacks() ),
			$this->getStore()->getEntityInfoBuilderFactory(),
			$this->getEntityContentFactory(),
			$this->getLanguageFallbackChainFactory(),
			TemplateFactory::getDefaultInstance(),
			$entityDataFormatProvider,
			// FIXME: Should this be done for all usages of this lookup, or is the impact of
			// CachingPropertyInfoLookup enough?
			new InProcessCachingDataTypeLookup( $this->getPropertyDataTypeLookup() ),
			$this->getLocalItemUriParser(),
			$this->getEntitySerializer(
				SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
				SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
			),
			$this->settings->getSetting( 'preferredGeoDataProperties' ),
			$this->settings->getSetting( 'preferredPageImagesProperties' ),
			$this->settings->getSetting( 'globeUris' )
		);
	}

	/**
	 * @return ViewFactory
	 */
	public function getViewFactory() {
		$lang = $this->getUserLanguage();

		$statementGrouperBuilder = new StatementGrouperBuilder(
			$this->settings->getSetting( 'statementSections' ),
			$this->getPropertyDataTypeLookup(),
			$this->getStatementGuidParser()
		);

		$propertyOrderProvider = new CachingPropertyOrderProvider(
			new WikiPagePropertyOrderProvider(
				Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' )
			),
			wfGetMainCache()
		);

		return new ViewFactory(
			$this->getEntityIdHtmlLinkFormatterFactory(),
			new EntityIdLabelFormatterFactory(),
			new WikibaseHtmlSnakFormatterFactory( $this->getSnakFormatterFactory() ),
			$statementGrouperBuilder->getStatementGrouper(),
			$propertyOrderProvider,
			$this->getSiteLookup(),
			$this->getDataTypeFactory(),
			TemplateFactory::getDefaultInstance(),
			$this->getLanguageNameLookup(),
			$this->getLanguageDirectionalityLookup(),
			new MediaWikiNumberLocalizer( $lang ),
			$this->settings->getSetting( 'siteLinkGroups' ),
			$this->settings->getSetting( 'specialSiteLinkGroups' ),
			$this->settings->getSetting( 'badgeItems' ),
			new MediaWikiLocalizedTextProvider( $lang->getCode() )
		);
	}

	/**
	 * @return LanguageDirectionalityLookup
	 */
	public function getLanguageDirectionalityLookup() {
		return new MediaWikiLanguageDirectionalityLookup();
	}

	/**
	 * @return DataTypeValidatorFactory
	 */
	public function getDataTypeValidatorFactory() {

		return new BuilderBasedDataTypeValidatorFactory(
			$this->dataTypeDefinitions->getValidatorFactoryCallbacks()
		);
	}

	/**
	 * @return DataTypeDefinitions
	 */
	public function getDataTypeDefinitions() {
		return $this->dataTypeDefinitions;
	}

	private function getMonolingualTextLanguages() {
		if ( $this->monolingualTextLanguages === null ) {
			// This has to be a superset of the language codes returned by
			// wikibase.WikibaseContentLanguages.
			// We don't want to have language codes in the suggester that are not
			// supported by the backend. The other way round is currently acceptable,
			// but will be fixed in T124758.
			$this->monolingualTextLanguages = new DifferenceContentLanguages(
				new UnionContentLanguages(
					new MediaWikiContentLanguages(),
					new StaticContentLanguages( array(
						// Special ISO 639-2 codes
						'und', 'mis', 'mul', 'zxx',

						// T150633
						'abe',

						// T125066
						'ett', 'fkv', 'koy', 'lkt', 'lld', 'smj',

						// T137115
						'non',

						// T138131
						'hai',

						// T137808
						'mnc',

						// T137809
						'otk',

						// T98314
						'tzl',

						// T151129
						'moe',
					) )
				),

				// MediaWiki language codes we don't want for monolingual text values
				new StaticContentLanguages( array(
					// Language codes that are not even well-formed BCP 47 language codes
					'simple',
					'tokipona',

					// Deprecated language codes with an alternative in MediaWiki
					'bat-smg', // => sgs
					'be-x-old', // => be-tarask
					'fiu-vro', // => vro
					'roa-rup', // => rup
					'zh-classical', // => lzh
					'zh-min-nan', // => nan
					'zh-yue', // => yue

					// Language codes we don't want for semantic reasons
					'de-formal',
					'nl-informal',
				) )
			);
		}
		return $this->monolingualTextLanguages;
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 *
	 * @return ContentLanguages
	 */
	public function getTermsLanguages() {
		return new MediaWikiContentLanguages();
	}

	/**
	 * @return CachingCommonsMediaFileNameLookup
	 */
	public function getCachingCommonsMediaFileNameLookup() {
		if ( $this->cachingCommonsMediaFileNameLookup === null ) {
			$this->cachingCommonsMediaFileNameLookup = new CachingCommonsMediaFileNameLookup(
				new MediaWikiPageNameNormalizer(),
				new HashBagOStuff()
			);
		}

		return $this->cachingCommonsMediaFileNameLookup;
	}

	public function getEntityTypesConfigValueProvider() {
		return new EntityTypesConfigValueProvider( $this->entityTypeDefinitions );
	}

	public function getSettingsValueProvider( $jsSetting, $phpSetting ) {
		return new SettingsValueProvider( $this->getSettings(), $jsSetting, $phpSetting );
	}

	/**
	 * Get configure unit converter.
	 * @return null|UnitConverter Configured Unit converter, or null if none configured
	 */
	public function getUnitConverter() {
		$unitStorage = $this->getUnitStorage();
		if ( !$unitStorage ) {
			return null;
		}
		return new UnitConverter( $unitStorage, $this->settings->getSetting( 'conceptBaseUri' ) );
	}

	/**
	 * Creates configured unit storage. Configuration is in unitStorage parameter,
	 * in getObjectFromSpec format.
	 * @see \ObjectFactory::getObjectFromSpec
	 * @return null|UnitStorage Configured unit storage, or null
	 */
	private function getUnitStorage() {
		if ( !$this->settings->hasSetting( 'unitStorage' ) ) {
			return null;
		}
		$storage =
			\ObjectFactory::getObjectFromSpec( $this->settings->getSetting( 'unitStorage' ) );
		if ( !( $storage instanceof UnitStorage ) ) {
			wfWarn( "Bad unit storage configuration, ignoring" );
			return null;
		}
		return $storage;
	}

	/**
	 * @return EntityRdfBuilderFactory
	 */
	public function getEntityRdfBuilderFactory() {
		if ( $this->entityRdfBuilderFactory === null ) {
			$this->entityRdfBuilderFactory = new EntityRdfBuilderFactory(
				$this->entityTypeDefinitions->getRdfBuilderFactoryCallbacks()
			);
		}

		return $this->entityRdfBuilderFactory;
	}

	/**
	 * @return string[] Associative array mapping names of known entity types (strings) to names of
	 *         repositories providing entities of those types.
	 *         Note: Currently entities of a given type are only provided by single repository. This
	 *         assumption can be changed in the future.
	 */
	public function getEntityTypeToRepositoryMapping() {
		return $this->repositoryDefinitions->getEntityTypeToRepositoryMapping();
	}

	/**
	 * @return string[] Associative array mapping repository names to base URIs of concept URIs.
	 */
	public function getConceptBaseUris() {
		return $this->repositoryDefinitions->getConceptBaseUris();
	}

}
