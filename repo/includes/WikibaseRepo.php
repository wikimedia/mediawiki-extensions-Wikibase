<?php

namespace Wikibase\Repo;

use CachedBagOStuff;
use Deserializers\DispatchableDeserializer;
use Exception;
use InvalidArgumentException;
use MWNamespace;
use ObjectCache;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterItemLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\IdGenerator;
use Wikibase\Lib\Changes\CentralIdLookupFactory;
use Wikibase\Lib\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use ExtensionRegistry;
use HashBagOStuff;
use Hooks;
use IContextSource;
use Language;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use MWException;
use Parser;
use Psr\Log\LoggerInterface;
use RequestContext;
use Serializers\Serializer;
use SiteLookup;
use StubObject;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdMissRecordingSimpleCache;
use Wikibase\Lib\Store\DelegatingEntityTermStoreWriter;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\MultiTermStoreWriter;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\SqlTypeIdsStore;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\DataAccess\WikibaseServices;
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
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\EntityFactory;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\ItemChange;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityIdLinkFormatter;
use Wikibase\Lib\EntityIdPlainLinkFormatter;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiNumberLocalizer;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Localizer\ChangeOpApplyExceptionLocalizer;
use Wikibase\Repo\Modules\PropertyValueExpertsModule;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\Modules\SettingsValueProvider;
use Wikibase\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Localizer\ChangeOpDeserializationExceptionLocalizer;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\NoFieldDefinitions;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\Units\UnitStorage;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\PropertyInfoBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
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
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\View\WikibaseHtmlSnakFormatterFactory;
use Wikibase\SettingsArray;
use Wikibase\SqlIdGenerator;
use Wikibase\SqlStore;
use Wikibase\Store;
use Wikibase\Store\EntityIdLookup;
use Wikibase\StringNormalizer;
use Wikibase\SummaryFormatter;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\UpsertSqlIdGenerator;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ViewFactory;
use Wikibase\WikibaseSettings;
use Wikimedia\ObjectFactory;

/**
 * Top level factory for the WikibaseRepo extension.
 *
 * @license GPL-2.0-or-later
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
	 * @var StatementGuidValidator|null
	 */
	private $statementGuidValidator = null;

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
	 * @var Store|null
	 */
	private $store = null;

	/**
	 * @var WikibaseContentLanguages|null
	 */
	private $wikibaseContentLanguages = null;

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
	 * @var WikibaseServices|null
	 */
	private $wikibaseServices = null;

	/**
	 * @var EntityRdfBuilderFactory|null
	 */
	private $entityRdfBuilderFactory = null;

	/**
	 * @var CachingKartographerEmbeddingHandler|null
	 */
	private $kartographerEmbeddingHandler = null;

	/**
	 * @var WikibaseRepo|null
	 */
	private static $instance = null;

	/**
	 * @var ValidatorBuilders|null
	 */
	private static $validatorBuilders = null;

	/**
	 * @var WikibaseValueFormatterBuilders|null
	 */
	private static $valueFormatterBuilders = null;

	/**
	 * @var WikibaseSnakFormatterBuilders|null
	 */
	private static $snakFormatterBuilders = null;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;

	public static function resetClassStatics() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new Exception(
				'Cannot reset WikibaseRepo class statics outside of tests.'
			);
		}
		self::$instance = null;
		self::$validatorBuilders = null;
		self::$valueFormatterBuilders = null;
		self::$snakFormatterBuilders = null;
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @throws MWException
	 * @return self
	 */
	private static function newInstance() {
		global $wgWBRepoDataTypes;

		if ( !is_array( $wgWBRepoDataTypes ) ) {
			throw new MWException( '$wgWBRepoDataTypes must be array. '
				. 'Maybe you forgot to require Wikibase.php in your LocalSettings.php?' );
		}

		$dataTypeDefinitions = $wgWBRepoDataTypes;
		Hooks::run( 'WikibaseRepoDataTypes', [ &$dataTypeDefinitions ] );

		$entityTypeDefinitionsArray = self::getDefaultEntityTypes();
		Hooks::run( 'WikibaseRepoEntityTypes', [ &$entityTypeDefinitionsArray ] );

		$settings = WikibaseSettings::getRepoSettings();

		$entityTypeDefinitions = new EntityTypeDefinitions( $entityTypeDefinitionsArray );

		return new self(
			$settings,
			new DataTypeDefinitions(
				$dataTypeDefinitions,
				$settings->getSetting( 'disabledDataTypes' )
			),
			$entityTypeDefinitions,
			self::getRepositoryDefinitionsFromSettings( $settings, $entityTypeDefinitions ),
			self::getEntitySourceDefinitionsFromSettings( $settings )
		);
	}

	/**
	 * @param SettingsArray $settings
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 *
	 * @return RepositoryDefinitions
	 */
	private static function getRepositoryDefinitionsFromSettings(
		SettingsArray $settings,
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		// FIXME: It might no longer be needed to check different settings (e.g. changesDatabase vs foreignRepositories)
		// once repository-related settings are unified, see: T153767.
		$repoDefinitions = [ '' => [
			'database' => $settings->getSetting( 'changesDatabase' ),
			'base-uri' => $settings->getSetting( 'conceptBaseUri' ),
			'prefix-mapping' => [ '' => '' ],
			'entity-namespaces' => $settings->getSetting( 'entityNamespaces' ),
		] ];

		$foreignRepositories = $settings->getSetting( 'foreignRepositories' );

		if ( isset( $foreignRepositories[''] ) ) {
			throw new MWException( 'A WikibaseRepo cannot have a foreign repo configured with '
				. 'the empty prefix, since the empty prefix always refers to the local repo.' );
		}

		foreach ( $foreignRepositories as $repository => $repositorySettings ) {
			$repoDefinitions[$repository] = [
				'database' => $repositorySettings['repoDatabase'],
				'base-uri' => $repositorySettings['baseUri'],
				'entity-namespaces' => $repositorySettings['entityNamespaces'],
				'prefix-mapping' => $repositorySettings['prefixMapping'],
			];
		}

		return new RepositoryDefinitions( $repoDefinitions, $entityTypeDefinitions );
	}

	private static function getEntitySourceDefinitionsFromSettings( SettingsArray $settings ) {
		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray( $settings->getSetting( 'entitySources' ) );
		}

		$localEntityNamespaces = $settings->getSetting( 'entityNamespaces' );
		$localDatabaseName = $settings->getSetting( 'changesDatabase' );
		$localConceptBaseUri = $settings->getSetting( 'conceptBaseUri' );

		$sources = [];

		$localEntityNamespaceSlotData = [];
		foreach ( $localEntityNamespaces as $entityType => $namespaceSlot ) {
			list( $namespaceId, $slot ) = self::splitNamespaceAndSlot( $namespaceSlot );
			$localEntityNamespaceSlotData[$entityType] = [
				'namespaceId' => $namespaceId,
				'slot' => $slot,
			];
		}
		$sources[] = new EntitySource( 'local', $localDatabaseName, $localEntityNamespaceSlotData, $localConceptBaseUri, '' );

		$foreignRepositories = $settings->getSetting( 'foreignRepositories' );

		if ( isset( $foreignRepositories[''] ) ) {
			throw new MWException( 'A WikibaseRepo cannot have a foreign repo configured with '
				. 'the empty prefix, since the empty prefix always refers to the local repo.' );
		}

		foreach ( $foreignRepositories as $repository => $repositorySettings ) {
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
	 * @return self
	 */
	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = self::newInstance();
		}

		return self::$instance;
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getDataTypeValidatorFactory() instead!
	 *
	 * @return ValidatorBuilders
	 */
	public static function getDefaultValidatorBuilders() {
		if ( self::$validatorBuilders === null ) {
			$wikibaseRepo = self::getDefaultInstance();
			self::$validatorBuilders = $wikibaseRepo->newValidatorBuilders();
		}

		return self::$validatorBuilders;
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
			$this->getDataAccessSettings(),
			$this->repositoryDefinitions->getEntityTypesPerRepository(),
			new MediaWikiPageNameNormalizer(),
			$this->settings->getSetting( 'geoShapeStorageApiEndpointUrl' ),
			$this->settings->getSetting( 'tabularDataStorageApiEndpointUrl' )
		);
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders() {
		if ( self::$valueFormatterBuilders === null ) {
			$wikibaseRepo = self::getDefaultInstance();
			self::$valueFormatterBuilders = $wikibaseRepo->newWikibaseValueFormatterBuilders();
		}

		return self::$valueFormatterBuilders;
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
			$this->settings->getSetting( 'geoShapeStorageBaseUrl' ),
			$this->settings->getSetting( 'tabularDataStorageBaseUrl' ),
			$this->getFormatterCache(),
			$this->settings->getSetting( 'sharedCacheDuration' ),
			$this->getEntityLookup(),
			$this->getEntityRevisionLookup(),
			$this->getEntityTitleLookup(),
			$this->getKartographerEmbeddingHandler(),
			$this->settings->getSetting( 'useKartographerMaplinkInWikitext' )
		);
	}

	/**
	 * @return CachingKartographerEmbeddingHandler|null
	 */
	public function getKartographerEmbeddingHandler() {
		if ( $this->kartographerEmbeddingHandler === null && $this->useKartographerGlobeCoordinateFormatter() ) {
			$this->kartographerEmbeddingHandler = new CachingKartographerEmbeddingHandler( new Parser() );
		}

		return $this->kartographerEmbeddingHandler;
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
		if ( self::$snakFormatterBuilders === null ) {
			self::$snakFormatterBuilders = self::getDefaultInstance()->newWikibaseSnakFormatterBuilders(
				self::getDefaultValueFormatterBuilders()
			);
		}

		return self::$snakFormatterBuilders;
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

	public function __construct(
		SettingsArray $settings,
		DataTypeDefinitions $dataTypeDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		RepositoryDefinitions $repositoryDefinitions,
		EntitySourceDefinitions $entitySourceDefinitions
	) {
		$this->settings = $settings;
		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->repositoryDefinitions = $repositoryDefinitions;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
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
			$this->entitySourceDefinitions,
			$this->getLocalEntitySource(),
			$this->getDataAccessSettings(),
			MediaWikiServices::getInstance()->getInterwikiLookup()
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
	public function getEntityDiffer() {
		$entityDiffer = new EntityDiffer();
		foreach ( $this->entityTypeDefinitions->getEntityDifferStrategyBuilders() as $builder ) {
			$entityDiffer->registerEntityDifferStrategy( call_user_func( $builder ) );
		}
		return $entityDiffer;
	}

	/**
	 * @return EntityPatcher
	 */
	public function getEntityPatcher() {
		$entityPatcher = new EntityPatcher();
		foreach ( $this->entityTypeDefinitions->getEntityPatcherStrategyBuilders() as $builder ) {
			$entityPatcher->registerEntityPatcherStrategy( call_user_func( $builder ) );
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
		return new TypeDispatchingEntityTitleStoreLookup(
			$this->entityTypeDefinitions->getEntityTitleStoreLookupFactoryCallbacks(),
			$this->getEntityContentFactory()
		);
	}

	/**
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		return $this->getEntityContentFactory();
	}

	public function getLocalRepoWikiPageMetaDataAccessor() : WikiPageEntityMetaDataAccessor {
		$entityNamespaceLookup = $this->getEntityNamespaceLookup();
		$repoName = ''; // Empty string here means this only works for the local repo
		$dbName = false; // false means the local database
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new TypeDispatchingWikiPageEntityMetaDataAccessor(
				$this->entityTypeDefinitions->getEntityMetaDataAccessorCallbacks(),
				new WikiPageEntityMetaDataLookup(
					$entityNamespaceLookup,
					new EntityIdLocalPartPageTableEntityQuery(
						$entityNamespaceLookup,
						MediaWikiServices::getInstance()->getSlotRoleStore()
					),
					$this->getLocalEntitySource(),
					$this->getDataAccessSettings(),
					$dbName,
					$repoName
				),
				$dbName,
				$repoName
			),
			LoggerFactory::getInstance( 'Wikibase' )
		);
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @param string $cache One of Store::LOOKUP_CACHING_*
	 *        Store::LOOKUP_CACHING_DISABLED to get an uncached direct lookup
	 *        Store::LOOKUP_CACHING_RETRIEVE_ONLY to get a lookup which reads from the cache, but doesn't store retrieved entities
	 *        Store::LOOKUP_CACHING_ENABLED to get a caching lookup (default)
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $cache = Store::LOOKUP_CACHING_ENABLED ) {
		return $this->getStore()->getEntityRevisionLookup( $cache );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityRevisionLookupFactoryCallbacks() {
		return $this->entityTypeDefinitions->getEntityRevisionLookupFactoryCallbacks();
	}

	/**
	 * @param User $user
	 * @param IContextSource $context
	 *
	 * @return ItemRedirectCreationInteractor
	 */
	public function newItemRedirectCreationInteractor( User $user, IContextSource $context ) {
		return new ItemRedirectCreationInteractor(
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
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
		return new MediawikiEditFilterHookRunner(
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
		return $this->getWikibaseServices()->getTermSearchInteractorFactory()->newInteractor(
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
	 * @return callable[]
	 */
	public function getEntityStoreFactoryCallbacks() {
		return $this->entityTypeDefinitions->getEntityStoreFactoryCallbacks();
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
				LoggerFactory::getInstance( 'Wikibase' ),
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
	 * @see Store::getEntityLookup
	 *
	 * @param string $cache One of Store::LOOKUP_CACHING_*
	 *        Store::LOOKUP_CACHING_DISABLED to get an uncached direct lookup
	 *        Store::LOOKUP_CACHING_RETRIEVE_ONLY to get a lookup which reads from the cache, but doesn't store retrieved entities
	 *        Store::LOOKUP_CACHING_ENABLED to get a caching lookup (default)
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $cache = Store::LOOKUP_CACHING_ENABLED ) {
		return $this->getStore()->getEntityLookup( $cache );
	}

	public function getPropertyLookup( $cacheMode = Store::LOOKUP_CACHING_ENABLED ): PropertyLookup {
		return new LegacyAdapterPropertyLookup( $this->getEntityLookup( $cacheMode ) );
	}

	public function getItemLookup( $cacheMode = Store::LOOKUP_CACHING_ENABLED ): ItemLookup {
		return new LegacyAdapterItemLookup( $this->getEntityLookup( $cacheMode ) );
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
		$snakValidator = new SnakValidator(
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory(),
			$this->getDataTypeValidatorFactory()
		);

		return new ChangeOpFactoryProvider(
			$this->getEntityConstraintProvider(),
			new GuidGenerator(),
			$this->getStatementGuidValidator(),
			$this->getStatementGuidParser(),
			$snakValidator,
			$this->getTermValidatorFactory(),
			$this->getSiteLookup(),
			array_keys( $this->settings->getSetting( 'badgeItems' ) )
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

	public function newIdGenerator() : IdGenerator {
		if ( $this->getSettings()->getSetting( 'idGenerator' ) === 'original' ) {
			return new SqlIdGenerator(
				MediaWikiServices::getInstance()->getDBLoadBalancer(),
				$this->getSettings()->getSetting( 'idBlacklist' ),
				$this->getSettings()->getSetting( 'idGeneratorSeparateDbConnection' )
			);
		}

		if ( $this->getSettings()->getSetting( 'idGenerator' ) === 'mysql-upsert' ) {
			// We could make sure the 'upsert' generator is only being used with mysql dbs here,
			// but perhaps that is an unnecessary check? People will realize when the DB query for
			// ID selection fails anyway...
			return new UpsertSqlIdGenerator(
				MediaWikiServices::getInstance()->getDBLoadBalancer(),
				$this->getSettings()->getSetting( 'idBlacklist' ),
				$this->getSettings()->getSetting( 'idGeneratorSeparateDbConnection' )
			);
		}

		throw new InvalidArgumentException(
			'idGenerator config option must be either \'original\' or \'mysql-upsert\''
		);
	}

	/**
	 * @return Store
	 */
	public function getStore() {
		if ( $this->store === null ) {
			$localEntitySource = $this->getLocalEntitySource();
			// TODO: the idea of local entity source seems not really suitable here. Store should probably
			// get source definitions and pass the right source/sources to services it creates accordingly
			// (as long as what it creates should not migrate to *SourceServices in the first place)

			$this->store = new SqlStore(
				$this->getEntityChangeFactory(),
				$this->getEntityIdParser(),
				$this->getEntityIdComposer(),
				$this->getEntityIdLookup(),
				$this->getEntityTitleLookup(),
				$this->getEntityNamespaceLookup(),
				$this->newIdGenerator(),
				$this->getWikibaseServices(),
				$localEntitySource
			);
		}

		return $this->store;
	}

	private function getLocalEntitySource() {
		if ( $this->getDataAccessSettings()->useEntitySourceBasedFederation() ) {
			$localEntitySourceName = $this->settings->getSetting( 'localEntitySourceName' );
			$sources = $this->entitySourceDefinitions->getSources();
			foreach ( $sources as $source ) {
				if ( $source->getSourceName() === $localEntitySourceName ) {
					return $source;
				}
			}
		}

		return new UnusableEntitySource();
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
		return $this->getWikibaseServices()->getTermBuffer();
	}

	/**
	 * @return SuffixEntityIdParser
	 */
	public function getLocalItemUriParser() {
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
				$this->repositoryDefinitions->getConceptBaseUris(),
				$entityDataTitle->getCanonicalURL() . '/',
				$languageCodes,
				$this->dataTypeDefinitions->getRdfTypeUris(),
				$this->settings->getSetting( 'pagePropertiesRdf' ) ?: [],
				$this->getSettings()->getSetting( 'rdfDataRightsUrl' )
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

			$this->exceptionLocalizer = new DispatchingExceptionLocalizer( $localizers );
		}

		return $this->exceptionLocalizer;
	}

	/**
	 * @param ValueFormatter $formatter
	 *
	 * @return ExceptionLocalizer[]
	 */
	private function getExceptionLocalizers( ValueFormatter $formatter ) {
		return [
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'ChangeOpValidationException' => new ChangeOpValidationExceptionLocalizer( $formatter ),
			'ChangeOpDeserializationException' => new ChangeOpDeserializationExceptionLocalizer(),
			'ChangeOpApplyException' => new ChangeOpApplyExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer()
		];
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
		// the links created in HtmlPageLinkRendererBeginHookHandler afterwards (the links must not
		// contain a display text: [[Item:Q1]] is fine but [[Item:Q1|Q1]] isn't).
		$idFormatter = new EntityIdPlainLinkFormatter( $this->getEntityTitleLookup() );

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
			[], // XXX: do we want $this->dataTypeDefinitions->getSnakFormatterFactoryCallbacks()
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
		global $wgAvailableRights;

		return new WikiPageEntityStorePermissionChecker(
			$this->getEntityNamespaceLookup(),
			$this->getEntityTitleLookup(),
			$wgAvailableRights
		);
	}

	/**
	 * @return TermValidatorFactory
	 */
	public function getTermValidatorFactory() {
		// Use the old deprecated setting if it exists
		if ( $this->settings->hasSetting( 'multilang-limits' ) ) {
			$constraints = $this->settings->getSetting( 'multilang-limits' );
		} else {
			$constraints = $this->settings->getSetting( 'string-limits' )['multilang'];
		}

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
	private function getLabelDescriptionDuplicateDetector() {
		return new LabelDescriptionDuplicateDetector( $this->getStore()->getLabelConflictFinder() );
	}

	/**
	 * @return SiteLookup
	 */
	public function getSiteLookup() {
		return MediaWikiServices::getInstance()->getSiteLookup();
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

		return new ChangeNotifier(
			$this->getEntityChangeFactory(),
			$transmitters,
			( new CentralIdLookupFactory() )->getCentralIdLookup()
		);
	}

	/**
	 * Get the mapping of entity types => content models
	 *
	 * @return array
	 */
	public function getContentModelMappings() {
		$map = $this->entityTypeDefinitions->getContentModelIds();

		Hooks::run( 'WikibaseContentModelMapping', [ &$map ] );

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
	 *  $wgWBRepoSettings['entityNamespaces'] setting.
	 *  This list will also include any sub entity types of entity types defined in $wgWBRepoSettings['entityNamespaces'].
	 *  Optionally the list also contains entity types from the configured foreign repositories.
	 */
	public function getEnabledEntityTypes() {
		return $this->repositoryDefinitions->getAllEntityTypes();
	}

	/**
	 * @return string[] List of entity type identifiers (typically "item" and "property")
	 *  that are configured in WikibaseRepo.entitytypes.php and enabled via the
	 *  $wgWBRepoSettings['entityNamespaces'] setting.
	 *  This list will also include any sub entity types of entity types defined in $wgWBRepoSettings['entityNamespaces'].
	 */
	public function getLocalEntityTypes() {
		// A blank string represents the local repo
		return $this->repositoryDefinitions->getEntityTypesPerRepository()[''];
	}

	/**
	 * @return EntityContentDataCodec
	 */
	public function getEntityContentDataCodec() {
		return new EntityContentDataCodec(
			$this->getEntityIdParser(),
			$this->getStorageEntitySerializer(),
			$this->getInternalFormatEntityDeserializer(),
			$this->settings->getSetting( 'maxSerializedEntitySize' ) * 1024
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
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the full
	 *  (expanded) serialization.
	 */
	public function getBaseDataModelSerializerFactory() {
		return $this->getWikibaseServices()->getBaseDataModelSerializerFactory();
	}

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the most
	 *  compact serialization.
	 */
	public function getCompactBaseDataModelSerializerFactory() {
		return $this->getWikibaseServices()->getCompactBaseDataModelSerializerFactory();
	}

	/**
	 * Returns a deserializer to deserialize entities in current serialization only.
	 *
	 * @return DispatchableDeserializer
	 */
	private function getAllTypesEntityDeserializer() {
		if ( $this->entityDeserializer === null ) {
			$deserializerFactoryCallbacks = $this->entityTypeDefinitions->getDeserializerFactoryCallbacks();
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
	 * Returns a deserializer to deserialize entities in both current and legacy serialization.
	 *
	 * @return Deserializer
	 */
	public function getInternalFormatEntityDeserializer() {
		return $this->getInternalFormatDeserializerFactory()->newEntityDeserializer();
	}

	/**
	 * @return Serializer Entity serializer that generates the full (expanded) serialization.
	 */
	public function getAllTypesEntitySerializer() {
		return $this->getWikibaseServices()->getFullEntitySerializer();
	}

	/**
	 * @return Serializer Entity serializer that generates the most compact serialization.
	 */
	public function getCompactEntitySerializer() {
		return $this->getWikibaseServices()->getCompactEntitySerializer();
	}

	/**
	 * Returns the entity serializer that generates serialization that is used in the storage layer.
	 *
	 * @return Serializer
	 */
	public function getStorageEntitySerializer() {
		return $this->getWikibaseServices()->getStorageEntitySerializer();
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
		return $this->getBaseDataModelDeserializerFactory()->newStatementDeserializer();
	}

	/**
	 * @return Serializer
	 */
	public function getStatementSerializer() {
		return $this->getBaseDataModelSerializerFactory()->newStatementSerializer();
	}

	/**
	 * @return Deserializer
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
				// TODO this should perhaps be factored out into a class
				if ( isset( $value['id'] ) ) {
					try {
						return new EntityIdValue( $this->getEntityIdParser()->parse( $value['id'] ) );
					} catch ( EntityIdParsingException $parsingException ) {
						throw new InvalidArgumentException(
							'Can not parse id \'' . $value['id'] . '\' to build EntityIdValue with',
							0,
							$parsingException
						);
					}
				} else {
					return EntityIdValue::newFromArray( $value );
				}
			},
		] );
	}

	public function newItemHandler(): ItemHandler {
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = $this->getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$siteLinkStore = $this->getStore()->newSiteLinkStore();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		return new ItemHandler(
			$this->newEntityTermStoreWriter(),
			$codec,
			$constraintProvider,
			$errorLocalizer,
			$this->getEntityIdParser(),
			$siteLinkStore,
			$this->getEntityIdLookup(),
			$this->getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->getFieldDefinitionsByType( Item::ENTITY_TYPE ),
			$this->getPropertyDataTypeLookup(),
			$legacyFormatDetector
		);
	}

	private function newEntityTermStoreWriter(): EntityTermStoreWriter {
		$termStoreMigrationStage = $this->settings->getSetting( 'tmpTermStoreMigrationStage' );

		if ( $termStoreMigrationStage === MIGRATION_WRITE_BOTH ) {
			return new MultiTermStoreWriter(
				$this->getOldEntityTermStoreWriter(),
				$this->getNewEntityTermStoreWriter()
			);
		}

		if ( $termStoreMigrationStage === MIGRATION_WRITE_NEW ) {
			return $this->getNewEntityTermStoreWriter();
		}

		return $this->getOldEntityTermStoreWriter();
	}

	private function getOldEntityTermStoreWriter(): EntityTermStoreWriter {
		return $this->getStore()->getTermIndex();
	}

	private function getNewEntityTermStoreWriter(): EntityTermStoreWriter {
		return new DelegatingEntityTermStoreWriter(
			$this->getPropertyTermStore(),
			$this->getItemTermStore()
		);
	}

	public function getPropertyTermStore(): PropertyTermStore {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$typeIdsStore = new SqlTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);

		return new DatabasePropertyTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$loadBalancer,
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			$this->getStringNormalizer()
		);
	}

	public function getItemTermStore(): ItemTermStore {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$typeIdsStore = new SqlTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);
		return new DatabaseItemTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$loadBalancer,
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			$this->getStringNormalizer()
		);
	}

	/**
	 * Get field definitions for entity depending on its type.
	 * @param string $type Entity type
	 * @return FieldDefinitions
	 */
	public function getFieldDefinitionsByType( $type ) {
		$definitions = $this->entityTypeDefinitions->getFieldDefinitions();
		if ( isset( $definitions[$type] ) && is_callable( $definitions[$type] ) ) {
			return call_user_func( $definitions[$type], $this->getTermsLanguages()->getLanguages(),
				$this->settings );
		}
		return new NoFieldDefinitions();
	}

	public function newPropertyHandler(): PropertyHandler {
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = $this->getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$propertyInfoStore = $this->getStore()->getPropertyInfoStore();
		$propertyInfoBuilder = $this->newPropertyInfoBuilder();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		return new PropertyHandler(
			$this->newEntityTermStoreWriter(),
			$codec,
			$constraintProvider,
			$errorLocalizer,
			$this->getEntityIdParser(),
			$this->getEntityIdLookup(),
			$this->getLanguageFallbackLabelDescriptionLookupFactory(),
			$propertyInfoStore,
			$propertyInfoBuilder,
			$this->getFieldDefinitionsByType( Property::ENTITY_TYPE ),
			$legacyFormatDetector
		);
	}

	/**
	 * @return PropertyInfoBuilder
	 */
	public function newPropertyInfoBuilder() {
		$propertyIdMap = [];

		$formatterUrlProperty = $this->settings->getSetting( 'formatterUrlProperty' );
		if ( $formatterUrlProperty !== null ) {
			$propertyIdMap[PropertyInfoLookup::KEY_FORMATTER_URL] = new PropertyId(
				$formatterUrlProperty
			);
		}

		$canonicalUriProperty = $this->settings->getSetting( 'canonicalUriProperty' );
		if ( $canonicalUriProperty !== null ) {
			$propertyIdMap[PropertyInfoStore::KEY_CANONICAL_URI] = new PropertyId( $canonicalUriProperty );
		}

		return new PropertyInfoBuilder( $propertyIdMap );
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
		return function ( $blob, $format ) {
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
		return new ApiHelperFactory(
			$this->getEntityTitleLookup(),
			$this->getExceptionLocalizer(),
			$this->getPropertyDataTypeLookup(),
			$this->getSiteLookup(),
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->newEditEntityFactory( $context ),
			$this->getBaseDataModelSerializerFactory(),
			$this->getAllTypesEntitySerializer(),
			$this->getEntityIdParser(),
			$this->getStore()->getEntityByLinkedTitleLookup(),
			$this->getEntityFactory(),
			$this->getEntityStore()
		);
	}

	/**
	 * @param IContextSource|null $context
	 *
	 * @return MediawikiEditEntityFactory
	 */
	public function newEditEntityFactory( IContextSource $context = null ) {
		return new MediawikiEditEntityFactory(
			$this->getEntityTitleLookup(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getEntityDiffer(),
			$this->getEntityPatcher(),
			$this->newEditFilterHookRunner( $context ?: RequestContext::getMain() ),
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			$this->getSettings()->getSetting( 'maxSerializedEntitySize' )
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
			$this->getChangeOpFactoryProvider()->getMergeFactory(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->getEntityStore(),
			$this->getEntityPermissionChecker(),
			$this->getSummaryFormatter(),
			$user,
			$this->newItemRedirectCreationInteractor( $user, $context ),
			$this->getEntityTitleLookup()
		);
	}

	/**
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	public function getLocalEntityNamespaces() {
		return $this->settings->getSetting( 'entityNamespaces' );
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		return $this->getWikibaseServices()->getEntityNamespaceLookup();
	}

	/**
	 * @return EntityIdHtmlLinkFormatterFactory
	 */
	public function getEntityIdHtmlLinkFormatterFactory() {
		return new EntityIdHtmlLinkFormatterFactory(
			$this->getEntityTitleLookup(),
			$this->getLanguageNameLookup(),
			$this->entityTypeDefinitions->getEntityIdHtmlLinkFormatterCallbacks()
		);
	}

	public function getEntityViewFactory() {
		return new DispatchingEntityViewFactory( $this->entityTypeDefinitions->getViewFactoryCallbacks() );
	}

	public function getEntityMetaTagsCreatorFactory() {
		return new DispatchingEntityMetaTagsCreatorFactory( $this->entityTypeDefinitions->getMetaTagsFactoryCallbacks() );
	}

	public function getEntityParserOutputGeneratorFactory(): EntityParserOutputGeneratorFactory {
		$entityDataFormatProvider = new EntityDataFormatProvider();
		$formats = $this->settings->getSetting( 'entityDataFormats' );
		$entityDataFormatProvider->setFormatWhiteList( $formats );

		return new EntityParserOutputGeneratorFactory(
			$this->getEntityViewFactory(),
			$this->getEntityMetaTagsCreatorFactory(),
			$this->getStore()->getEntityInfoBuilder(),
			$this->getEntityTitleLookup(),
			$this->getLanguageFallbackChainFactory(),
			TemplateFactory::getDefaultInstance(),
			$entityDataFormatProvider,
			// FIXME: Should this be done for all usages of this lookup, or is the impact of
			// CachingPropertyInfoLookup enough?
			new InProcessCachingDataTypeLookup( $this->getPropertyDataTypeLookup() ),
			$this->getCompactEntitySerializer(),
			new EntityReferenceExtractorDelegator(
				$this->entityTypeDefinitions->getEntityReferenceExtractorCallbacks(),
				new StatementEntityReferenceExtractor( $this->getLocalItemUriParser() )
			),
			$this->getKartographerEmbeddingHandler(),
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			$this->settings->getSetting( 'preferredGeoDataProperties' ),
			$this->settings->getSetting( 'preferredPageImagesProperties' ),
			$this->settings->getSetting( 'globeUris' )
		);
	}

	public function getEntityParserOutputGenerator( Language $userLanguage ): EntityParserOutputGenerator {
		return $this->getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( $userLanguage );
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
			ObjectCache::getLocalClusterInstance()
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
			new MediaWikiLanguageDirectionalityLookup(),
			new MediaWikiNumberLocalizer( $lang ),
			$this->settings->getSetting( 'siteLinkGroups' ),
			$this->settings->getSetting( 'specialSiteLinkGroups' ),
			$this->settings->getSetting( 'badgeItems' ),
			new MediaWikiLocalizedTextProvider( $lang ),
			new RepoSpecialPageLinker()
		);
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

	public function getWikibaseContentLanguages() {
		if ( $this->wikibaseContentLanguages === null ) {
			$this->wikibaseContentLanguages = WikibaseContentLanguages::getDefaultInstance();
		}

		return $this->wikibaseContentLanguages;
	}

	private function getMonolingualTextLanguages() {
		return $this->getWikibaseContentLanguages()->getContentLanguages( 'monolingualtext' );
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
	 * @return CachingCommonsMediaFileNameLookup
	 */
	private function getCachingCommonsMediaFileNameLookup() {
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
		return new SettingsValueProvider( $this->settings, $jsSetting, $phpSetting );
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
	 * @see ObjectFactory::getObjectFromSpec
	 * @return null|UnitStorage Configured unit storage, or null
	 */
	private function getUnitStorage() {
		if ( !$this->settings->hasSetting( 'unitStorage' ) ) {
			return null;
		}
		$storage =
			ObjectFactory::getObjectFromSpec( $this->settings->getSetting( 'unitStorage' ) );
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
	 * @param IContextSource $contextSource
	 * @return EntityDiffVisualizerFactory
	 */
	public function getEntityDiffVisualizerFactory( IContextSource $contextSource ) {
		$langCode = $contextSource->getLanguage()->getCode();

		$options = new FormatterOptions( [
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $langCode
		] );

		$termLookup = new EntityRetrievingTermLookup( $this->getEntityLookup() );
		$labelDescriptionLookupFactory = new LanguageFallbackLabelDescriptionLookupFactory(
			$this->getLanguageFallbackChainFactory(),
			$termLookup
		);

		$htmlFormatterFactory = $this->getEntityIdHtmlLinkFormatterFactory();
		$entityIdFormatter = $htmlFormatterFactory->getEntityIdFormatter( $contextSource->getLanguage() );

		$formatterFactory = $this->getSnakFormatterFactory();
		$detailedSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_DIFF, $options );
		$terseSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML, $options );

		return new EntityDiffVisualizerFactory(
			$this->entityTypeDefinitions->getEntityDiffVisualizerCallbacks(),
			$contextSource,
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			new ClaimDifferenceVisualizer(
				new DifferencesSnakVisualizer(
					$entityIdFormatter,
					$detailedSnakFormatter,
					$terseSnakFormatter,
					$langCode
				),
				$langCode
			),
			$this->getSiteLookup(),
			$entityIdFormatter
		);
	}

	/**
	 * @return array[] Associative array mapping names of known entity types (strings) to lists of names of
	 *         repositories providing entities of those types.
	 *         Note: Currently entities of a given type are only provided by single repository. This
	 *         assumption can be changed in the future.
	 */
	public function getEntityTypeToRepositoryMapping() {
		if ( $this->getDataAccessSettings()->useEntitySourceBasedFederation() ) {
			// Map all entity types to unprefixed repository.
			// TODO: This is a bit of a hack but does the job for EntityIdSearchHelper as long as there are no
			// prefixed IDs in the entity source realm. Probably EntityIdSearchHelper should be changed instead
			// of getting this map passed from Repo
			$entityTypes = array_keys( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() );
			return array_fill_keys( $entityTypes, [ '' ] );
		}
		return $this->repositoryDefinitions->getEntityTypeToRepositoryMapping();
	}

	/**
	 * @return string[] Associative array mapping repository or entity source names to base URIs of concept URIs.
	 */
	public function getConceptBaseUris() {
		if ( $this->getDataAccessSettings()->useEntitySourceBasedFederation() ) {
			return $this->entitySourceDefinitions->getConceptBaseUris();
		}
		return $this->repositoryDefinitions->getConceptBaseUris();
	}

	public function getPropertyValueExpertsModule() {
		return new PropertyValueExpertsModule( $this->getDataTypeDefinitions() );
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

	public function getDataAccessSettings() {
		if ( $this->dataAccessSettings === null ) {
			$this->dataAccessSettings = new DataAccessSettings(
				$this->settings->getSetting( 'maxSerializedEntitySize' ),
				$this->settings->getSetting( 'useTermsTableSearchFields' ),
				$this->settings->getSetting( 'forceWriteTermsTableSearchFields' ),
				$this->settings->getSetting( 'useEntitySourceBasedFederation' )
			);
		}
		return $this->dataAccessSettings;
	}

	public function getEntitySourceDefinitions() {
		return $this->entitySourceDefinitions;
	}

	private function getMultiRepositoryServiceWiring() {
		return require __DIR__ . '/../../data-access/src/MultiRepositoryServiceWiring.php';
	}

	private function getPerRepositoryServiceWiring() {
		return require __DIR__ . '/../../data-access/src/PerRepositoryServiceWiring.php';
	}

	/**
	 * Get entity search helper callbacks.
	 * @return callable[]
	 */
	public function getEntitySearchHelperCallbacks() {
		return $this->entityTypeDefinitions->getEntitySearchHelperCallbacks();
	}

	public function getEntityLinkFormatterFactory( Language $language ) {
		return new EntityLinkFormatterFactory( $language, $this->entityTypeDefinitions->getLinkFormatterCallbacks() );
	}

	/**
	 * Get entity search helper callbacks.
	 * @return string[]
	 */
	public function getFulltextSearchTypes() {
		$searchTypes = $this->entityTypeDefinitions->getFulltextSearchTypes();
		foreach ( $searchTypes as $key => $value ) {
			if ( is_callable( $value ) ) {
				$searchTypes[$key] = $value();
			}
		}
		return $searchTypes;
	}

	/**
	 *
	 * @fixme this is duplicated in WikibaseClient...
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
			'wikibase.repo.formatter.',
			$cacheSecret
		);

		$cache->setLogger( $this->getLogger() );

		$cache = new StatsdMissRecordingSimpleCache(
			$cache,
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			'wikibase.repo.formatterCache.miss'
		);

		return $cache;
	}

	public function getLogger(): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikibase' );
	}

}
