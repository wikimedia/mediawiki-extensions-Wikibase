<?php

declare( strict_types = 1 );

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\DispatchingDeserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use Psr\Log\LoggerInterface;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueParsers\NullParser;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataAccess\SourceAndTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\EntityIdLinkFormatter;
use Wikibase\Lib\Formatters\EntityIdPlainLinkFormatter;
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\NumberLocalizerFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\MediaWikiMessageInLanguageProvider;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikibase\Lib\Modules\PropertyValueExpertsModule;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;
use Wikibase\Lib\Normalization\StringValueNormalizer;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\SourceDispatchingPropertyDataTypeLookup;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\ItemTermStoreWriterAdapter;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\PropertyTermStoreWriterAdapter;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\SourceAndTypeDispatchingArticleIdLookup;
use Wikibase\Lib\Store\SourceAndTypeDispatchingExistenceChecker;
use Wikibase\Lib\Store\SourceAndTypeDispatchingRedirectChecker;
use Wikibase\Lib\Store\SourceAndTypeDispatchingTitleTextLookup;
use Wikibase\Lib\Store\SourceAndTypeDispatchingUrlLookup;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\ThrowingEntityTermStoreWriter;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\Units\UnitStorage;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\FedPropertiesTypeDispatchingEntitySearchHelper;
use Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Content\ContentHandlerEntityIdLookup;
use Wikibase\Repo\Content\ContentHandlerEntityTitleLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\EntityTypesConfigFeddyPropsAugmenter;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\FederatedProperties\BaseUriExtractor;
use Wikibase\Repo\FederatedProperties\DefaultFederatedPropertiesEntitySourceAdder;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesAwareDispatchingEntityIdParser;
use Wikibase\Repo\FederatedProperties\WrappingEntityIdFormatterFactory;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\LocalizedTextProviderFactory;
use Wikibase\Repo\Localizer\ChangeOpApplyExceptionLocalizer;
use Wikibase\Repo\Localizer\ChangeOpDeserializationExceptionLocalizer;
use Wikibase\Repo\Localizer\ChangeOpValidationExceptionLocalizer;
use Wikibase\Repo\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Localizer\GenericExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageParameterFormatter;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\Normalization\CommonsMediaValueNormalizer;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\HookChangeTransmitter;
use Wikibase\Repo\Notifications\WikiPageActionEntityChangeFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\PropertyServices;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\SnakFactory;
use Wikibase\Repo\StatementGrouperBuilder;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
use Wikibase\Repo\Store\CompositeSiteLinkConflictLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\DispatchStats;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\Repo\Store\Sql\SqlStore;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\ValidatorBuilders;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\View\WikibaseHtmlSnakFormatterFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ViewFactory;
use Wikimedia\ObjectFactory\ObjectFactory;

/** @phpcs-require-sorted-array */
return [

	'WikibaseRepo.AliasTermBuffer' => function ( MediaWikiServices $services ): AliasTermBuffer {
		return WikibaseRepo::getPrefetchingTermLookup( $services );
	},

	'WikibaseRepo.AllTypesEntityDeserializer' => function ( MediaWikiServices $services ): DispatchableDeserializer {
		$deserializerFactoryCallbacks = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK );
		$baseDeserializerFactory = WikibaseRepo::getBaseDataModelDeserializerFactory( $services );
		$deserializers = [];
		foreach ( $deserializerFactoryCallbacks as $callback ) {
			$deserializers[] = call_user_func( $callback, $baseDeserializerFactory );
		}
		return new DispatchingDeserializer( $deserializers );
	},

	'WikibaseRepo.AllTypesEntitySerializer' => function ( MediaWikiServices $services ): Serializer {
		$serializerFactoryCallbacks = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK );
		$baseSerializerFactory = WikibaseRepo::getBaseDataModelSerializerFactory( $services );
		$serializers = [];

		foreach ( $serializerFactoryCallbacks as $callback ) {
			$serializers[] = $callback( $baseSerializerFactory );
		}

		return new DispatchingSerializer( $serializers );
	},

	'WikibaseRepo.ApiHelperFactory' => function ( MediaWikiServices $services ): ApiHelperFactory {
		$store = WikibaseRepo::getStore( $services );

		return new ApiHelperFactory(
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			WikibaseRepo::getExceptionLocalizer( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			$services->getSiteLookup(),
			WikibaseRepo::getSummaryFormatter( $services ),
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getBaseDataModelSerializerFactory( $services ),
			WikibaseRepo::getAllTypesEntitySerializer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			$services->getPermissionManager(),
			$services->getRevisionLookup(),
			$services->getTitleFactory(),
			$store->getEntityByLinkedTitleLookup(),
			WikibaseRepo::getEntityFactory( $services ),
			WikibaseRepo::getEntityStore( $services )
		);
	},

	'WikibaseRepo.BagOStuffSiteLinkConflictLookup' => function (
		MediaWikiServices $services
	): BagOStuffSiteLinkConflictLookup {
		return new BagOStuffSiteLinkConflictLookup(
			ObjectCache::getLocalClusterInstance()
		);
	},

	'WikibaseRepo.BaseDataModelDeserializerFactory' => function ( MediaWikiServices $services ): DeserializerFactory {
		return new DeserializerFactory(
			WikibaseRepo::getDataValueDeserializer( $services ),
			WikibaseRepo::getEntityIdParser( $services )
		);
	},

	'WikibaseRepo.BaseDataModelSerializerFactory' => function ( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT );
	},

	'WikibaseRepo.CachingCommonsMediaFileNameLookup' => function (
		MediaWikiServices $services
	): CachingCommonsMediaFileNameLookup {
		return new CachingCommonsMediaFileNameLookup(
			new MediaWikiPageNameNormalizer(),
			new HashBagOStuff()
		);
	},

	'WikibaseRepo.ChangeHolder' => function ( MediaWikiServices $services ): ChangeHolder {
		return new ChangeHolder();
	},

	'WikibaseRepo.ChangeNotifier' => function ( MediaWikiServices $services ): ChangeNotifier {
		$transmitters = [
			new HookChangeTransmitter(
				$services->getHookContainer(),
				'WikibaseChangeNotification'
			),
		];

		$settings = WikibaseRepo::getSettings( $services );
		if ( $settings->getSetting( 'useChangesTable' ) ) {
			$transmitters[] = WikibaseRepo::getChangeHolder( $services );
		}

		return new ChangeNotifier(
			new WikiPageActionEntityChangeFactory(
				WikibaseRepo::getEntityChangeFactory( $services ),
				$services->getCentralIdLookupFactory()->getNonLocalLookup()
			),
			$transmitters
		);
	},

	'WikibaseRepo.ChangeOpDeserializerFactory' => function ( MediaWikiServices $services ): ChangeOpDeserializerFactory {
		$changeOpFactoryProvider = WikibaseRepo::getChangeOpFactoryProvider( $services );
		$settings = WikibaseRepo::getSettings( $services );

		return new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( WikibaseRepo::getTermsLanguages( $services ) ),
			WikibaseRepo::getSiteLinkBadgeChangeOpSerializationValidator( $services ),
			WikibaseRepo::getExternalFormatStatementDeserializer( $services ),
			WikibaseRepo::getSiteLinkPageNormalizer( $services ),
			WikibaseRepo::getSiteLinkTargetProvider( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityLookup( $services ),
			WikibaseRepo::getStringNormalizer( $services ),
			$settings->getSetting( 'siteLinkGroups' )
		);
	},

	'WikibaseRepo.ChangeOpFactoryProvider' => function ( MediaWikiServices $services ): ChangeOpFactoryProvider {
		$snakValidator = new SnakValidator(
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeFactory( $services ),
			WikibaseRepo::getDataTypeValidatorFactory( $services )
		);

		$settings = WikibaseRepo::getSettings( $services );

		return new ChangeOpFactoryProvider(
			WikibaseRepo::getEntityConstraintProvider( $services ),
			new GuidGenerator(),
			WikibaseRepo::getStatementGuidValidator( $services ),
			WikibaseRepo::getStatementGuidParser( $services ),
			$snakValidator,
			WikibaseRepo::getTermValidatorFactory( $services ),
			$services->getSiteLookup(),
			WikibaseRepo::getSnakNormalizer( $services ),
			WikibaseRepo::getReferenceNormalizer( $services ),
			WikibaseRepo::getStatementNormalizer( $services ),
			array_keys( $settings->getSetting( 'badgeItems' ) ),
			$settings->getSetting( 'tmpNormalizeDataValues' )
		);
	},

	'WikibaseRepo.CommonsMediaValueNormalizer' => function ( MediaWikiServices $services ): CommonsMediaValueNormalizer {
		return new CommonsMediaValueNormalizer(
			WikibaseRepo::getCachingCommonsMediaFileNameLookup( $services ),
			WikibaseRepo::getLogger( $services )
		);
	},

	'WikibaseRepo.CompactBaseDataModelSerializerFactory' => function ( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
	},

	'WikibaseRepo.CompactEntitySerializer' => function ( MediaWikiServices $services ): Serializer {
		$serializerFactoryCallbacks = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK );
		$baseSerializerFactory = WikibaseRepo::getCompactBaseDataModelSerializerFactory( $services );
		$serializers = [];

		foreach ( $serializerFactoryCallbacks as $callback ) {
			$serializers[] = $callback( $baseSerializerFactory );
		}

		return new DispatchingSerializer( $serializers );
	},

	'WikibaseRepo.ContentModelMappings' => function ( MediaWikiServices $services ): array {
		$map = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::CONTENT_MODEL_ID );

		$services->getHookContainer()
			->run( 'WikibaseContentModelMapping', [ &$map ] );

		return $map;
	},

	'WikibaseRepo.DataAccessSettings' => function ( MediaWikiServices $services ): DataAccessSettings {
		return new DataAccessSettings(
			WikibaseRepo::getSettings( $services )->getSetting( 'maxSerializedEntitySize' )
		);
	},

	'WikibaseRepo.DatabaseTypeIdsStore' => function ( MediaWikiServices $services ): DatabaseTypeIdsStore {
		return new DatabaseTypeIdsStore(
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb(),
			$services->getMainWANObjectCache()
		);
	},

	'WikibaseRepo.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

		$dataTypes = wfArrayPlus2d(
			$repoDataTypes,
			$baseDataTypes
		);

		$services->getHookContainer()->run( 'WikibaseRepoDataTypes', [ &$dataTypes ] );

		$settings = WikibaseRepo::getSettings( $services );

		return new DataTypeDefinitions(
			$dataTypes,
			$settings->getSetting( 'disabledDataTypes' )
		);
	},

	'WikibaseRepo.DataTypeFactory' => function ( MediaWikiServices $services ): DataTypeFactory {
		return new DataTypeFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )->getValueTypes()
		);
	},

	'WikibaseRepo.DataTypeValidatorFactory' => function ( MediaWikiServices $services ): DataTypeValidatorFactory {
		return new BuilderBasedDataTypeValidatorFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )->getValidatorFactoryCallbacks()
		);
	},

	'WikibaseRepo.DataValueDeserializer' => function ( MediaWikiServices $services ): DataValueDeserializer {
		return new DataValueDeserializer( [
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => static function ( $value ) use ( $services ) {
				// TODO this should perhaps be factored out into a class
				if ( isset( $value['id'] ) ) {
					try {
						return new EntityIdValue( WikibaseRepo::getEntityIdParser( $services )->parse( $value['id'] ) );
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
	},

	'WikibaseRepo.DataValueFactory' => function ( MediaWikiServices $services ): DataValueFactory {
		return new DataValueFactory( WikibaseRepo::getDataValueDeserializer( $services ) );
	},

	'WikibaseRepo.DefaultSnakFormatterBuilders' => function ( MediaWikiServices $services ): WikibaseSnakFormatterBuilders {
		return new WikibaseSnakFormatterBuilders(
			WikibaseRepo::getDefaultValueFormatterBuilders( $services ),
			WikibaseRepo::getStore( $services )->getPropertyInfoLookup(),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeFactory( $services )
		);
	},

	/**
	 * Returns a low level factory object for creating validators for well known data types.
	 *
	 * @warning This is for use with {@link WikibaseRepo::getDefaultValidatorBuilders()} during bootstrap only!
	 * Program logic should use {@link WikibaseRepo::getDataTypeValidatorFactory()} instead!
	 */
	'WikibaseRepo.DefaultValidatorBuilders' => function ( MediaWikiServices $services ): ValidatorBuilders {
		$settings = WikibaseRepo::getSettings( $services );

		return new ValidatorBuilders(
			WikibaseRepo::getEntityLookup( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			$settings->getSetting( 'urlSchemes' ),
			WikibaseRepo::getItemVocabularyBaseUri( $services ),
			WikibaseRepo::getMonolingualTextLanguages( $services ),
			WikibaseRepo::getCachingCommonsMediaFileNameLookup( $services ),
			new MediaWikiPageNameNormalizer(), // TODO should probably inject an HttpRequestFactory here (or get from $services?)
			$settings->getSetting( 'geoShapeStorageApiEndpointUrl' ),
			$settings->getSetting( 'tabularDataStorageApiEndpointUrl' )
		);
	},

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with {@link WikibaseRepo::getDefaultValueFormatterBuilders()} during bootstrap only!
	 * Program logic should use {@link WikibaseRepo::getSnakFormatterFactory()} instead!
	 */
	'WikibaseRepo.DefaultValueFormatterBuilders' => function (
		MediaWikiServices $services
	): WikibaseValueFormatterBuilders {
		$settings = WikibaseRepo::getSettings( $services );
		$termFallbackCache = WikibaseRepo::getTermFallbackCache( $services );
		$redirectResolvingLatestRevisionLookup = WikibaseRepo::getRedirectResolvingLatestRevisionLookup( $services );

		return new WikibaseValueFormatterBuilders(
			new FormatterLabelDescriptionLookupFactory(
				WikibaseRepo::getTermLookup( $services ),
				$termFallbackCache,
				$redirectResolvingLatestRevisionLookup
			),
			WikibaseRepo::getLanguageNameLookup( $services ),
			WikibaseRepo::getItemUrlParser( $services ),
			$settings->getSetting( 'geoShapeStorageBaseUrl' ),
			$settings->getSetting( 'tabularDataStorageBaseUrl' ),
			$termFallbackCache,
			WikibaseRepo::getEntityLookup( $services ),
			$redirectResolvingLatestRevisionLookup,
			$settings->getSetting( 'entitySchemaNamespace' ),
			WikibaseRepo::getEntityExistenceChecker( $services ),
			WikibaseRepo::getEntityTitleTextLookup( $services ),
			WikibaseRepo::getEntityUrlLookup( $services ),
			WikibaseRepo::getEntityRedirectChecker( $services ),
			$services->getLanguageFactory(),
			WikibaseRepo::getEntityTitleLookup( $services ),
			WikibaseRepo::getKartographerEmbeddingHandler( $services ),
			$settings->getSetting( 'useKartographerMaplinkInWikitext' ),
			$services->getMainConfig()->get( 'ThumbLimits' )
		);
	},

	'WikibaseRepo.DispatchStats' => function ( MediaWikiServices $services ): DispatchStats {
		return new DispatchStats(
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb()
		);
	},

	'WikibaseRepo.EditEntityFactory' => function ( MediaWikiServices $services ): MediawikiEditEntityFactory {
		return new MediawikiEditEntityFactory(
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			WikibaseRepo::getStore( $services )
				->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityStore( $services ),
			WikibaseRepo::getEntityPermissionChecker( $services ),
			WikibaseRepo::getEntityDiffer( $services ),
			WikibaseRepo::getEntityPatcher( $services ),
			WikibaseRepo::getEditFilterHookRunner( $services ),
			$services->getStatsdDataFactory(),
			$services->getUserOptionsLookup(),
			WikibaseRepo::getSettings( $services )->getSetting( 'maxSerializedEntitySize' ),
			WikibaseRepo::getLocalEntityTypes( $services )
		);
	},

	'WikibaseRepo.EditFilterHookRunner' => function ( MediaWikiServices $services ): EditFilterHookRunner {
		return new MediawikiEditFilterHookRunner(
			WikibaseRepo::getEntityNamespaceLookup( $services ),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			WikibaseRepo::getEntityContentFactory( $services )
		);
	},

	'WikibaseRepo.EnabledEntityTypes' => function ( MediaWikiServices $services ): array {
		$types = array_keys(
			WikibaseRepo::getEntitySourceDefinitions( $services )
				->getEntityTypeToDatabaseSourceMapping()
		);
		$subEntityTypes = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SUB_ENTITY_TYPES );

		return array_reduce(
			$types,
			function ( $entityTypes, $type ) use ( $subEntityTypes ) {
				$entityTypes[] = $type;
				if ( array_key_exists( $type, $subEntityTypes ) ) {
					$entityTypes = array_merge( $entityTypes, $subEntityTypes[$type] );
				}
				return $entityTypes;
			},
			[]
		);
	},

	'WikibaseRepo.EntityArticleIdLookup' => function ( MediaWikiServices $services ): EntityArticleIdLookup {
		$callbacks = WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )->getServiceBySourceAndType(
				EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK
			);

		return new SourceAndTypeDispatchingArticleIdLookup(
			WikibaseRepo::getEntitySourceLookup( $services ),
			new ServiceBySourceAndTypeDispatcher(
				EntityArticleIdLookup::class,
				$callbacks
			)
		);
	},

	'WikibaseRepo.EntityChangeFactory' => function ( MediaWikiServices $services ): EntityChangeFactory {
		//TODO: take this from a setting or registry.
		$changeClasses = [
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		];

		return new EntityChangeFactory(
			WikibaseRepo::getEntityDiffer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			$changeClasses,
			EntityChange::class,
			WikibaseRepo::getLogger( $services )
		);
	},

	'WikibaseRepo.EntityChangeLookup' => function ( MediaWikiServices $services ): EntityChangeLookup {
		return new EntityChangeLookup(
			WikibaseRepo::getEntityChangeFactory( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb()
		);
	},

	'WikibaseRepo.EntityChangeOpProvider' => function ( MediaWikiServices $services ): EntityChangeOpProvider {
		return new EntityChangeOpProvider(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::CHANGEOP_DESERIALIZER_CALLBACK )
		);
	},

	'WikibaseRepo.EntityConstraintProvider' => function ( MediaWikiServices $services ): EntityConstraintProvider {
		return new EntityConstraintProvider(
			new CompositeSiteLinkConflictLookup( [
				new SqlSiteLinkConflictLookup(
					WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb(),
					WikibaseRepo::getEntityIdComposer( $services )
				),
				WikibaseRepo::getBagOStuffSiteLinkConflictLookup( $services ),
			] ),
			WikibaseRepo::getTermValidatorFactory( $services ),
			WikibaseRepo::getSettings( $services )->getSetting( 'redirectBadgeItems' )
		);
	},

	'WikibaseRepo.EntityContentDataCodec' => function ( MediaWikiServices $services ): EntityContentDataCodec {
		return new EntityContentDataCodec(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getStorageEntitySerializer( $services ),
			WikibaseRepo::getInternalFormatEntityDeserializer( $services ),
			WikibaseRepo::getDataAccessSettings( $services )
				->maxSerializedEntitySizeInBytes()
		);
	},

	'WikibaseRepo.EntityContentFactory' => function ( MediaWikiServices $services ): EntityContentFactory {
		return new EntityContentFactory(
			WikibaseRepo::getContentModelMappings( $services ),
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK )
		);
	},

	'WikibaseRepo.EntityDataFormatProvider' => function ( MediaWikiServices $services ): EntityDataFormatProvider {
		$formats = WikibaseRepo::getSettings( $services )->getSetting( 'entityDataFormats' );

		$entityDataFormatProvider = new EntityDataFormatProvider();
		$entityDataFormatProvider->setAllowedFormats( $formats );

		return $entityDataFormatProvider;
	},

	'WikibaseRepo.EntityDataSerializationService' => function ( MediaWikiServices $services ): EntityDataSerializationService {
		return new EntityDataSerializationService(
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getEntityDataFormatProvider( $services ),
			WikibaseRepo::getBaseDataModelSerializerFactory( $services ),
			WikibaseRepo::getAllTypesEntitySerializer( $services ),
			$services->getSiteLookup(),
			WikibaseRepo::getRdfBuilderFactory( $services ),
			WikibaseRepo::getEntityIdParser( $services )
		);
	},

	'WikibaseRepo.EntityDataUriManager' => function ( MediaWikiServices $services ): EntityDataUriManager {
		$entityDataFormatProvider = WikibaseRepo::getEntityDataFormatProvider( $services );

		// build a mapping of formats to file extensions and include HTML
		$supportedExtensions = [];
		$supportedExtensions['html'] = 'html';
		foreach ( $entityDataFormatProvider->getSupportedFormats() as $format ) {
			$ext = $entityDataFormatProvider->getExtension( $format );

			if ( $ext !== null ) {
				$supportedExtensions[$format] = $ext;
			}
		}

		return new EntityDataUriManager(
			// TODO this should probably use SpecialPageFactory or TitleFactory,
			// but neither of them seems to have a suitable method yet
			SpecialPage::getTitleFor( 'EntityData' ),
			$supportedExtensions,
			WikibaseRepo::getSettings( $services )
				->getSetting( 'entityDataCachePaths' ),
			WikibaseRepo::getEntityTitleLookup( $services )
		);
	},

	'WikibaseRepo.EntityDiffer' => function ( MediaWikiServices $services ): EntityDiffer {
		$entityDiffer = new EntityDiffer();
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
		$builders = $entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_DIFFER_STRATEGY_BUILDER );
		foreach ( $builders as $builder ) {
			$entityDiffer->registerEntityDifferStrategy( $builder() );
		}
		return $entityDiffer;
	},

	'WikibaseRepo.EntityDiffVisualizerFactory' => function ( MediaWikiServices $services ): EntityDiffVisualizerFactory{
		return new EntityDiffVisualizerFactory(
			WikibaseRepo::getEntityTypeDefinitions( $services )->get( EntityTypeDefinitions::ENTITY_DIFF_VISUALIZER_CALLBACK ),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			$services->getSiteLookup(),
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory( $services ),
			WikibaseRepo::getSnakFormatterFactory( $services )
		);
	},

	'WikibaseRepo.EntityExistenceChecker' => function ( MediaWikiServices $services ): EntityExistenceChecker {
		return new SourceAndTypeDispatchingExistenceChecker(
			WikibaseRepo::getEntitySourceLookup( $services ),
			new ServiceBySourceAndTypeDispatcher(
				EntityExistenceChecker::class,
				WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )
				->getServiceBySourceAndType( EntityTypeDefinitions::EXISTENCE_CHECKER_CALLBACK )
			)
		);
	},

	'WikibaseRepo.EntityFactory' => function ( MediaWikiServices $services ): EntityFactory {
		$instantiators = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::ENTITY_FACTORY_CALLBACK );

		return new EntityFactory( $instantiators );
	},

	'WikibaseRepo.EntityIdComposer' => function ( MediaWikiServices $services ): EntityIdComposer {
		return new EntityIdComposer(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_ID_COMPOSER_CALLBACK )
		);
	},

	'WikibaseRepo.EntityIdHtmlLinkFormatterFactory' => function (
		MediaWikiServices $services
	): EntityIdFormatterFactory {
		$factory = new EntityIdHtmlLinkFormatterFactory(
			WikibaseRepo::getEntityTitleLookup( $services ),
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK )
		);

		$federatedPropertiesEnabled = WikibaseRepo::getSettings( $services )
			->getSetting( 'federatedPropertiesEnabled' );

		return $federatedPropertiesEnabled
			? new WrappingEntityIdFormatterFactory( $factory )
			: $factory;
	},

	'WikibaseRepo.EntityIdLabelFormatterFactory' => function (
		MediaWikiServices $services
	): EntityIdLabelFormatterFactory {
		return new EntityIdLabelFormatterFactory(
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
		);
	},

	'WikibaseRepo.EntityIdLookup' => function ( MediaWikiServices $services ): EntityIdLookup {
		return new ContentHandlerEntityIdLookup( WikibaseRepo::getEntityContentFactory( $services ) );
	},

	'WikibaseRepo.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		$settings = WikibaseRepo::getSettings( $services );
		$dispatchingEntityIdParser = new DispatchingEntityIdParser(
			WikibaseRepo::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);

		if ( $settings->getSetting( 'federatedPropertiesEnabled' ) ) {
			$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions( $services );
			return new FederatedPropertiesAwareDispatchingEntityIdParser(
				$dispatchingEntityIdParser,
				new BaseUriExtractor(),
				$entitySourceDefinitions
			);
		}

		return $dispatchingEntityIdParser;
	},

	'WikibaseRepo.EntityLinkFormatterFactory' => function ( MediaWikiServices $services ): EntityLinkFormatterFactory {
		return new EntityLinkFormatterFactory(
			WikibaseRepo::getEntityTitleTextLookup( $services ),
			$services->getLanguageFactory(),
			WikibaseRepo::getEntityTypeDefinitions( $services )->get( EntityTypeDefinitions::LINK_FORMATTER_CALLBACK )
		);
	},

	'WikibaseRepo.EntityLookup' => function ( MediaWikiServices $services ): EntityLookup {
		return WikibaseRepo::getStore( $services )
			->getEntityLookup(
				Store::LOOKUP_CACHING_ENABLED,
				LookupConstants::LATEST_FROM_REPLICA
			);
	},

	'WikibaseRepo.EntityMetaTagsCreatorFactory' => function (
		MediaWikiServices $services
	): DispatchingEntityMetaTagsCreatorFactory {
		return new DispatchingEntityMetaTagsCreatorFactory(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::META_TAGS_CREATOR_CALLBACK )
		);
	},

	'WikibaseRepo.EntityNamespaceLookup' => function ( MediaWikiServices $services ): EntityNamespaceLookup {
		$entitySources = array_filter(
			WikibaseRepo::getEntitySourceDefinitions( $services )->getSources(),
			function ( EntitySource $entitySource ) {
				return $entitySource->getType() === DatabaseEntitySource::TYPE;
			}
		);

		return array_reduce(
			$entitySources,
			function ( EntityNamespaceLookup $nsLookup, DatabaseEntitySource $source ): EntityNamespaceLookup {
				return $nsLookup->merge( new EntityNamespaceLookup(
					$source->getEntityNamespaceIds(),
					$source->getEntitySlotNames()
				) );
			},
			new EntityNamespaceLookup( [], [] )
		);
	},

	'WikibaseRepo.EntityParserOutputGeneratorFactory' => function ( MediaWikiServices $services ): EntityParserOutputGeneratorFactory {
		$settings = WikibaseRepo::getSettings( $services );

		return new EntityParserOutputGeneratorFactory(
			WikibaseRepo::getEntityViewFactory( $services ),
			WikibaseRepo::getEntityMetaTagsCreatorFactory( $services ),
			WikibaseRepo::getEntityTitleLookup( $services ),
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getEntityDataFormatProvider( $services ),
			// FIXME: Should this be done for all usages of this lookup, or is the impact of
			// CachingPropertyInfoLookup enough?
			new InProcessCachingDataTypeLookup(
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			),
			new EntityReferenceExtractorDelegator(
				WikibaseRepo::getEntityTypeDefinitions( $services )
					->get( EntityTypeDefinitions::ENTITY_REFERENCE_EXTRACTOR_CALLBACK ),
				new StatementEntityReferenceExtractor(
					WikibaseRepo::getItemUrlParser( $services )
				)
			),
			WikibaseRepo::getKartographerEmbeddingHandler( $services ),
			$services->getStatsdDataFactory(),
			$services->getRepoGroup(),
			$services->getLinkBatchFactory(),
			$settings->getSetting( 'preferredGeoDataProperties' ),
			$settings->getSetting( 'preferredPageImagesProperties' ),
			$settings->getSetting( 'globeUris' )
		);
	},

	'WikibaseRepo.EntityPatcher' => function ( MediaWikiServices $services ): EntityPatcher {
		$entityPatcher = new EntityPatcher();
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
		$builders = $entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_PATCHER_STRATEGY_BUILDER );
		foreach ( $builders as $builder ) {
			$entityPatcher->registerEntityPatcherStrategy( $builder() );
		}
		return $entityPatcher;
	},

	'WikibaseRepo.EntityPermissionChecker' => function ( MediaWikiServices $services ): EntityPermissionChecker {
		return new WikiPageEntityStorePermissionChecker(
			WikibaseRepo::getEntityNamespaceLookup( $services ),
			WikibaseRepo::getEntityTitleLookup( $services ),
			$services->getPermissionManager(),
			$services->getMainConfig()->get( 'AvailableRights' )
		);
	},

	'WikibaseRepo.EntityRdfBuilderFactory' => function ( MediaWikiServices $services ): EntityRdfBuilderFactory {
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );

		return new EntityRdfBuilderFactory(
			$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_BUILDER_FACTORY_CALLBACK )
		);
	},

	'WikibaseRepo.EntityRedirectChecker' => function ( MediaWikiServices $services ): EntityRedirectChecker {
		return new SourceAndTypeDispatchingRedirectChecker(
			new ServiceBySourceAndTypeDispatcher(
				EntityRedirectChecker::class,
				WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )
					->getServiceBySourceAndType( EntityTypeDefinitions::REDIRECT_CHECKER_CALLBACK )
			),
			WikibaseRepo::getEntitySourceLookup( $services )
		);
	},

	'WikibaseRepo.EntityRevisionLookup' => function ( MediaWikiServices $services ): EntityRevisionLookup {
		return WikibaseRepo::getStore( $services )
			->getEntityRevisionLookup( Store::LOOKUP_CACHING_ENABLED );
	},

	'WikibaseRepo.EntitySearchHelper' => function ( MediaWikiServices $services ): EntitySearchHelper {
		$entitySearchHelperCallbacks = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK );

		$typeDispatchingEntitySearchHelper = new TypeDispatchingEntitySearchHelper(
			$entitySearchHelperCallbacks,
			RequestContext::getMain()->getRequest()
		);

		if ( WikibaseRepo::getSettings( $services )->getSetting( 'federatedPropertiesEnabled' ) === true ) {
			return new FedPropertiesTypeDispatchingEntitySearchHelper(
				new CombinedEntitySearchHelper( [
					$typeDispatchingEntitySearchHelper,
					WikibaseRepo::getFederatedPropertiesServiceFactory( $services )->newApiEntitySearchHelper(),
				] ),
				$typeDispatchingEntitySearchHelper
			);
		}

		return $typeDispatchingEntitySearchHelper;
	},

	'WikibaseRepo.EntitySearchHelperCallbacks' => function ( MediaWikiServices $services ): array {
		return WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK );
	},

	'WikibaseRepo.EntitySourceAndTypeDefinitions' => function ( MediaWikiServices $services ): EntitySourceAndTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

		$entityTypes = wfArrayPlus2d(
			$repoEntityTypes,
			$baseEntityTypes
		);

		$services->getHookContainer()->run( 'WikibaseRepoEntityTypes', [ &$entityTypes ] );

		$entityTypeDefinitionsBySourceType = [ DatabaseEntitySource::TYPE => new EntityTypeDefinitions( $entityTypes ) ];

		if ( WikibaseRepo::getSettings( $services )->getSetting( 'federatedPropertiesEnabled' ) ) {
			$entityTypeDefinitionsBySourceType[ApiEntitySource::TYPE] = new EntityTypeDefinitions(
				EntityTypesConfigFeddyPropsAugmenter::factory()->override( $entityTypes )
			);
		}

		return new EntitySourceAndTypeDefinitions(
			$entityTypeDefinitionsBySourceType,
			WikibaseRepo::getEntitySourceDefinitions( $services )->getSources()
		);
	},

	'WikibaseRepo.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseRepo::getSettings( $services );
		$subEntityTypesMapper = WikibaseRepo::getSubEntityTypesMapper( $services );

		$configParser = new EntitySourceDefinitionsConfigParser();

		$entitySourceDefinitions = $configParser->newDefinitionsFromConfigArray(
			$settings->getSetting( 'entitySources' ),
			$subEntityTypesMapper
		);

		$fedPropsSourceAdder = new DefaultFederatedPropertiesEntitySourceAdder(
			$settings->getSetting( 'federatedPropertiesEnabled' ),
			$settings->getSetting( 'federatedPropertiesSourceScriptUrl' ),
			$subEntityTypesMapper
		);

		return $fedPropsSourceAdder->addDefaultIfRequired( $entitySourceDefinitions );
	},

	'WikibaseRepo.EntitySourceLookup' => function ( MediaWikiServices $services ): EntitySourceLookup {
		return new EntitySourceLookup(
			WikibaseRepo::getEntitySourceDefinitions( $services ),
			WikibaseRepo::getSubEntityTypesMapper( $services )
		);
	},

	'WikibaseRepo.EntityStore' => function ( MediaWikiServices $services ): EntityStore {
		return WikibaseRepo::getStore( $services )->getEntityStore();
	},

	'WikibaseRepo.EntityStoreWatcher' => function ( MediaWikiServices $services ): EntityStoreWatcher {
		return WikibaseRepo::getStore( $services )->getEntityStoreWatcher();
	},

	'WikibaseRepo.EntityStubRdfBuilderFactory' => function ( MediaWikiServices $services ): EntityStubRdfBuilderFactory {
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );

		return new EntityStubRdfBuilderFactory(
			$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_BUILDER_STUB_FACTORY_CALLBACK )
		);
	},

	'WikibaseRepo.EntityTitleLookup' => function ( MediaWikiServices $services ): EntityTitleLookup {
		return WikibaseRepo::getEntityTitleStoreLookup( $services );
	},

	'WikibaseRepo.EntityTitleStoreLookup' => function ( MediaWikiServices $services ): EntityTitleStoreLookup {
		return new TypeDispatchingEntityTitleStoreLookup(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_TITLE_STORE_LOOKUP_FACTORY_CALLBACK ),
			new ContentHandlerEntityTitleLookup(
				WikibaseRepo::getEntityContentFactory( $services ),
				WikibaseRepo::getEntitySourceDefinitions( $services ),
				WikibaseRepo::getLocalEntitySource( $services ),
				$services->getInterwikiLookup()
			)
		);
	},

	'WikibaseRepo.EntityTitleTextLookup' => function ( MediaWikiServices $services ): EntityTitleTextLookup {
		return new SourceAndTypeDispatchingTitleTextLookup(
			WikibaseRepo::getEntitySourceLookup( $services ),
			new ServiceBySourceAndTypeDispatcher(
				EntityTitleTextLookup::class,
				WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )
				->getServiceBySourceAndType( EntityTypeDefinitions::TITLE_TEXT_LOOKUP_CALLBACK )
			)
		);
	},

	'WikibaseRepo.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

		$entityTypes = wfArrayPlus2d(
			$repoEntityTypes,
			$baseEntityTypes
		);

		$services->getHookContainer()->run( 'WikibaseRepoEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseRepo.EntityTypesConfigValue' => function ( MediaWikiServices $services ): array {
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
		return [
			'types' => $entityTypeDefinitions->getEntityTypes(),
			'deserializer-factory-functions' => $entityTypeDefinitions
				->get( EntityTypeDefinitions::JS_DESERIALIZER_FACTORY_FUNCTION ),
		];
	},

	'WikibaseRepo.EntityTypeToRepositoryMapping' => function ( MediaWikiServices $services ): array {
		// Map all entity types to unprefixed repository.
		// TODO: This is a bit of a hack but does the job for EntityIdSearchHelper as long as there are no
		// prefixed IDs in the entity source realm. Probably EntityIdSearchHelper should be changed instead
		// of getting this map passed from Repo
		$entityTypes = array_keys(
			WikibaseRepo::getEntitySourceDefinitions( $services )->getEntityTypeToDatabaseSourceMapping()
		);
		return array_fill_keys( $entityTypes, [ '' ] );
	},

	'WikibaseRepo.EntityUrlLookup' => function ( MediaWikiServices $services ): EntityUrlLookup {
		return new SourceAndTypeDispatchingUrlLookup(
			new ServiceBySourceAndTypeDispatcher(
				EntityUrlLookup::class,
				WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )
					->getServiceBySourceAndType( EntityTypeDefinitions::URL_LOOKUP_CALLBACK )
			),
			WikibaseRepo::getEntitySourceLookup( $services )
		);
	},

	'WikibaseRepo.EntityViewFactory' => function ( MediaWikiServices $services ): DispatchingEntityViewFactory {
		return new DispatchingEntityViewFactory(
			WikibaseRepo::getEntityTypeDefinitions( $services )->get( EntityTypeDefinitions::VIEW_FACTORY_CALLBACK )
		);
	},

	'WikibaseRepo.ExceptionLocalizer' => function ( MediaWikiServices $services ): ExceptionLocalizer {
		$formatter = WikibaseRepo::getMessageParameterFormatter( $services );

		return new DispatchingExceptionLocalizer( [
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'ChangeOpValidationException' => new ChangeOpValidationExceptionLocalizer( $formatter ),
			'ChangeOpDeserializationException' => new ChangeOpDeserializationExceptionLocalizer(),
			'ChangeOpApplyException' => new ChangeOpApplyExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer(),
		] );
	},

	'WikibaseRepo.ExternalFormatStatementDeserializer' => function ( MediaWikiServices $services ): Deserializer {
		return WikibaseRepo::getBaseDataModelDeserializerFactory( $services )->newStatementDeserializer();
	},

	'WikibaseRepo.FallbackLabelDescriptionLookupFactory' => function (
		MediaWikiServices $services
	): FallbackLabelDescriptionLookupFactory {
		return new FallbackLabelDescriptionLookupFactory(
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getRedirectResolvingLatestRevisionLookup( $services ),
			WikibaseRepo::getTermFallbackCache( $services ),
			WikibaseRepo::getTermLookup( $services ),
			WikibaseRepo::getTermBuffer( $services )
		);
	},

	'WikibaseRepo.FederatedPropertiesServiceFactory' => function ( MediaWikiServices $services ): ApiServiceFactory {
		$settings = WikibaseRepo::getSettings( $services );

		if (
			!$settings->getSetting( 'federatedPropertiesEnabled' ) ||
			!$settings->hasSetting( 'federatedPropertiesSourceScriptUrl' )
		) {
			throw new LogicException(
				'Federated Property services should not be constructed when federatedProperties feature is not enabled or configured.'
			);
		}
		$entitySourceDefinition = WikibaseRepo::getEntitySourceDefinitions( $services );

		return new ApiServiceFactory(
			$services->getHttpRequestFactory(),
			WikibaseRepo::getContentModelMappings( $services ),
			WikibaseRepo::getDataTypeDefinitions( $services ),
			$entitySourceDefinition,
			$settings->getSetting( 'federatedPropertiesSourceScriptUrl' ),
			$services->getMainConfig()->get( 'ServerName' )
		);
	},

	'WikibaseRepo.FieldDefinitionsFactory' => function ( MediaWikiServices $services ): FieldDefinitionsFactory {
		return new FieldDefinitionsFactory(
			WikibaseRepo::getEntityTypeDefinitions( $services ),
			WikibaseRepo::getTermsLanguages( $services ),
			WikibaseRepo::getSettings( $services )
		);
	},

	'WikibaseRepo.FulltextSearchTypes' => function ( MediaWikiServices $services ): array {
		$searchTypeContexts = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::FULLTEXT_SEARCH_CONTEXT );

		return array_map( function ( $context ): string {
			return is_callable( $context ) ? $context() : $context;
		}, $searchTypeContexts );
	},

	'WikibaseRepo.IdGenerator' => function ( MediaWikiServices $services ): IdGenerator {
		$settings = WikibaseRepo::getSettings( $services );
		$idGeneratorSetting = $settings->getSetting( 'idGenerator' );
		$db = WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb();

		if ( $idGeneratorSetting === 'auto' ) {
			$idGeneratorSetting = $db->connections()->getWriteConnection()->getType() === 'mysql'
				? 'mysql-upsert' : 'original';
		}

		switch ( $idGeneratorSetting ) {
			case 'original':
				$idGenerator = new SqlIdGenerator(
					$db,
					$settings->getSetting( 'reservedIds' ),
					$settings->getSetting( 'idGeneratorSeparateDbConnection' )
				);
				break;
			case 'mysql-upsert':
				// We could make sure the 'upsert' generator is only being used with mysql dbs here,
				// but perhaps that is an unnecessary check? People will realize when the DB query for
				// ID selection fails anyway...
				$idGenerator = new UpsertSqlIdGenerator(
					$db,
					$settings->getSetting( 'reservedIds' ),
					$settings->getSetting( 'idGeneratorSeparateDbConnection' )
				);
				break;
			default:
				throw new InvalidArgumentException(
					'idGenerator config option must be \'original\', \'mysql-upsert\' or \'auto\''
				);
		}

		return new RateLimitingIdGenerator(
			$idGenerator,
			RequestContext::getMain()
		);
	},

	'WikibaseRepo.InternalFormatDeserializerFactory' => function ( MediaWikiServices $services ): InternalDeserializerFactory {
		return new InternalDeserializerFactory(
			WikibaseRepo::getDataValueDeserializer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getAllTypesEntityDeserializer( $services )
		);
	},

	'WikibaseRepo.InternalFormatEntityDeserializer' => function ( MediaWikiServices $services ): Deserializer {
		return WikibaseRepo::getInternalFormatDeserializerFactory( $services )->newEntityDeserializer();
	},

	'WikibaseRepo.ItemHandler' => function ( MediaWikiServices $services ): ItemHandler {
		return new ItemHandler(
			WikibaseRepo::getItemTermStoreWriter( $services ),
			WikibaseRepo::getEntityContentDataCodec( $services ),
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getValidatorErrorLocalizer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getStore( $services )
				->newSiteLinkStore(),
			WikibaseRepo::getBagOStuffSiteLinkConflictLookup( $services ),
			WikibaseRepo::getEntityIdLookup( $services ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services ),
			WikibaseRepo::getFieldDefinitionsFactory( $services )
				->getFieldDefinitionsByType( Item::ENTITY_TYPE ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services )
				->newRepoDb(),
			WikibaseRepo::getLegacyFormatDetectorCallback( $services )
		);
	},

	'WikibaseRepo.ItemMergeInteractor' => function ( MediaWikiServices $services ): ItemMergeInteractor {
		return new ItemMergeInteractor(
			WikibaseRepo::getChangeOpFactoryProvider( $services )
				->getMergeFactory(),
			WikibaseRepo::getStore( $services )
				->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityStore( $services ),
			WikibaseRepo::getEntityPermissionChecker( $services ),
			WikibaseRepo::getSummaryFormatter( $services ),
			WikibaseRepo::getItemRedirectCreationInteractor( $services ),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			$services->getPermissionManager()
		);
	},

	'WikibaseRepo.ItemRedirectCreationInteractor' => function (
		MediaWikiServices $services
	): ItemRedirectCreationInteractor {
		$store = WikibaseRepo::getStore( $services );

		return new ItemRedirectCreationInteractor(
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			WikibaseRepo::getEntityStore( $services ),
			WikibaseRepo::getEntityPermissionChecker( $services ),
			WikibaseRepo::getSummaryFormatter( $services ),
			WikibaseRepo::getEditFilterHookRunner( $services ),
			$store->getEntityRedirectLookup(),
			WikibaseRepo::getEntityTitleStoreLookup( $services )
		);
	},

	'WikibaseRepo.ItemTermsCollisionDetector' => function ( MediaWikiServices $services ): TermsCollisionDetector {
		return WikibaseRepo::getTermsCollisionDetectorFactory( $services )
			->getTermsCollisionDetector( Item::ENTITY_TYPE );
	},

	'WikibaseRepo.ItemTermStoreWriter' => function ( MediaWikiServices $services ): EntityTermStoreWriter {
		if ( !in_array(
			Item::ENTITY_TYPE,
			WikibaseRepo::getLocalEntitySource( $services )->getEntityTypes()
		) ) {
			return new ThrowingEntityTermStoreWriter();
		}

		return new ItemTermStoreWriterAdapter(
			WikibaseRepo::getTermStoreWriterFactory( $services )->newItemTermStoreWriter()
		);
	},

	'WikibaseRepo.ItemUrlParser' => function ( MediaWikiServices $services ): SuffixEntityIdParser {
		return new SuffixEntityIdParser(
			WikibaseRepo::getItemVocabularyBaseUri( $services ),
			new ItemIdParser()
		);
	},

	'WikibaseRepo.ItemVocabularyBaseUri' => function ( MediaWikiServices $services ): string {
		$itemSource = WikibaseRepo::getEntitySourceDefinitions( $services )
			->getDatabaseSourceForEntityType( Item::ENTITY_TYPE );

		if ( $itemSource === null ) {
			throw new LogicException( 'No source providing Items configured!' );
		}

		return $itemSource->getConceptBaseUri();
	},

	'WikibaseRepo.KartographerEmbeddingHandler' => function ( MediaWikiServices $services ): ?CachingKartographerEmbeddingHandler {
		$settings = WikibaseRepo::getSettings( $services );

		if (
			$settings->getSetting( 'useKartographerGlobeCoordinateFormatter' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' )
		) {
			return new CachingKartographerEmbeddingHandler(
				$services->getParserFactory()->create()
			);
		} else {
			return null;
		}
	},

	'WikibaseRepo.LanguageDirectionalityLookup' => function ( MediaWikiServices $services ): LanguageDirectionalityLookup {
		return new MediaWikiLanguageDirectionalityLookup(
			$services->getLanguageFactory(),
			$services->getLanguageNameUtils()
		);
	},

	'WikibaseRepo.LanguageFallbackChainFactory' => function ( MediaWikiServices $services ): LanguageFallbackChainFactory {
		return new LanguageFallbackChainFactory(
			WikibaseRepo::getTermsLanguages( $services ),
			$services->getLanguageFactory(),
			$services->getLanguageConverterFactory(),
			$services->getLanguageFallback()
		);
	},

	'WikibaseRepo.LanguageNameLookup' => function ( MediaWikiServices $services ): LanguageNameLookup {
		$userLanguage = WikibaseRepo::getUserLanguage( $services );
		return WikibaseRepo::getLanguageNameLookupFactory( $services )
			->getForLanguage( $userLanguage );
	},

	'WikibaseRepo.LanguageNameLookupFactory' => function ( MediaWikiServices $services ): LanguageNameLookupFactory {
		return new LanguageNameLookupFactory( $services->getLanguageNameUtils() );
	},

	'WikibaseRepo.LegacyFormatDetectorCallback' => function ( MediaWikiServices $services ): ?callable {
		$transformOnExport = WikibaseRepo::getSettings( $services )
			->getSetting( 'transformLegacyFormatOnExport' );

		if ( !$transformOnExport ) {
			return null;
		}

		/**
		 * Detects blobs that may be using a legacy serialization format.
		 * WikibaseRepo uses this for the $legacyExportFormatDetector parameter
		 * when constructing EntityHandlers.
		 *
		 * @see WikibaseRepo::getItemHandler
		 * @see WikibaseRepo::getPropertyHandler
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
	},

	'WikibaseRepo.LinkTargetEntityIdLookup' => function ( MediaWikiServices $services ): LinkTargetEntityIdLookup {
		return new EntityLinkTargetEntityIdLookup(
			WikibaseRepo::getEntityNamespaceLookup( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntitySourceDefinitions( $services ),
			WikibaseRepo::getLocalEntitySource( $services )
		);
	},

	'WikibaseRepo.LocalEntityNamespaceLookup' => function ( MediaWikiServices $services ): EntityNamespaceLookup {
		$localEntitySource = WikibaseRepo::getLocalEntitySource( $services );
		$nsIds = $localEntitySource->getEntityNamespaceIds();
		$entitySlots = $localEntitySource->getEntitySlotNames();

		return new EntityNamespaceLookup( $nsIds, $entitySlots );
	},

	'WikibaseRepo.LocalEntitySource' => function ( MediaWikiServices $services ): EntitySource {
		$localEntitySourceName = WikibaseRepo::getSettings( $services )->getSetting( 'localEntitySourceName' );
		$sources = WikibaseRepo::getEntitySourceDefinitions( $services )->getSources();
		foreach ( $sources as $source ) {
			if ( $source->getSourceName() === $localEntitySourceName ) {
				return $source;
			}
		}

		throw new LogicException( 'No source configured: ' . $localEntitySourceName );
	},

	'WikibaseRepo.LocalEntityTypes' => function ( MediaWikiServices $services ): array {
		$localSource = WikibaseRepo::getLocalEntitySource( $services );
		$subEntityTypes = WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::SUB_ENTITY_TYPES );

		// Expands the array of local entity types with sub types
		return array_reduce(
			$localSource->getEntityTypes(),
			function ( $types, $localTypeName ) use ( $subEntityTypes ) {
				$types[] = $localTypeName;
				if ( array_key_exists( $localTypeName, $subEntityTypes ) ) {
					$types = array_merge( $types, $subEntityTypes[$localTypeName] );
				}
				return $types;
			},
			[]
		);
	},

	'WikibaseRepo.LocalizedTextProviderFactory' => function (
		MediaWikiServices $services
	): LocalizedTextProviderFactory {
		return new LocalizedTextProviderFactory(
			$services->getLanguageFactory()
		);
	},

	'WikibaseRepo.LocalRepoWikiPageMetaDataAccessor' => function ( MediaWikiServices $services ): WikiPageEntityMetaDataAccessor {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup( $services );
		$repoName = ''; // Empty string here means this only works for the local repo
		$dbName = false; // false means the local database
		$logger = WikibaseRepo::getLogger( $services );

		$entitySource = WikibaseRepo::getLocalEntitySource( $services );
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new TypeDispatchingWikiPageEntityMetaDataAccessor(
				WikibaseRepo::getEntityTypeDefinitions( $services )
					->get( EntityTypeDefinitions::ENTITY_METADATA_ACCESSOR_CALLBACK ),
				new WikiPageEntityMetaDataLookup(
					$entityNamespaceLookup,
					new EntityIdLocalPartPageTableEntityQuery(
						$entityNamespaceLookup,
						$services->getSlotRoleStore()
					),
					$entitySource,
					WikibaseRepo::getRepoDomainDbFactory( $services )->newForEntitySource( $entitySource ),
					$logger
				),
				$dbName,
				$repoName
			),
			$logger
		);
	},

	'WikibaseRepo.Logger' => function ( MediaWikiServices $services ): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikibase' );
	},

	'WikibaseRepo.MatchingTermsLookupFactory' => function ( MediaWikiServices $services ): MatchingTermsLookupFactory {
		return new MatchingTermsLookupFactory(
			WikibaseRepo::getEntityIdComposer( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			$services->getMainWANObjectCache()
		);
	},

	'WikibaseRepo.MessageInLanguageProvider' => function ( MediaWikiServices $services ): MessageInLanguageProvider {
		return new MediaWikiMessageInLanguageProvider();
	},

	'WikibaseRepo.MessageParameterFormatter' => function ( MediaWikiServices $services ): ValueFormatter {
		$formatterOptions = new FormatterOptions();
		$valueFormatterFactory = WikibaseRepo::getValueFormatterFactory( $services );

		return new MessageParameterFormatter(
			$valueFormatterFactory->getValueFormatter( SnakFormatter::FORMAT_WIKI, $formatterOptions ),
			new EntityIdLinkFormatter( WikibaseRepo::getEntityTitleLookup( $services ) ),
			$services->getSiteLookup(),
			WikibaseRepo::getUserLanguage( $services )
		);
	},

	'WikibaseRepo.MonolingualTextLanguages' => function ( MediaWikiServices $services ): ContentLanguages {
		return WikibaseRepo::getWikibaseContentLanguages( $services )
			->getContentLanguages( WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT );
	},

	'WikibaseRepo.NumberLocalizerFactory' => function ( MediaWikiServices $services ): NumberLocalizerFactory {
		return new NumberLocalizerFactory(
			$services->getLanguageFactory()
		);
	},

	'WikibaseRepo.PrefetchingTermLookup' => function ( MediaWikiServices $services ): PrefetchingTermLookup {
		return new SourceAndTypeDispatchingPrefetchingTermLookup(
			new ServiceBySourceAndTypeDispatcher(
				PrefetchingTermLookup::class,
				WikibaseRepo::getEntitySourceAndTypeDefinitions( $services )
					->getServiceBySourceAndType( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK )
			),
			WikibaseRepo::getEntitySourceLookup( $services )
		);
	},

	'WikibaseRepo.PropertyDataTypeLookup' => function ( MediaWikiServices $services ): PropertyDataTypeLookup {
		$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions( $services );

		return new SourceDispatchingPropertyDataTypeLookup(
			new EntitySourceLookup(
				$entitySourceDefinitions,
				new SubEntityTypesMapper( WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::SUB_ENTITY_TYPES ) )
			),
			( new PropertyServices(
				$entitySourceDefinitions,
				PropertyServices::getServiceDefinitions()
			) )->get( PropertyServices::PROPERTY_DATA_TYPE_LOOKUP_CALLBACK )
		);
	},

	'WikibaseRepo.PropertyHandler' => function ( MediaWikiServices $services ): PropertyHandler {
		return new PropertyHandler(
			WikibaseRepo::getPropertyTermStoreWriter( $services ),
			WikibaseRepo::getEntityContentDataCodec( $services ),
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getValidatorErrorLocalizer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityIdLookup( $services ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services ),
			WikibaseRepo::getStore( $services )
				->getPropertyInfoStore(),
			WikibaseRepo::getPropertyInfoBuilder( $services ),
			WikibaseRepo::getFieldDefinitionsFactory( $services )
				->getFieldDefinitionsByType( Property::ENTITY_TYPE ),
			WikibaseRepo::getLegacyFormatDetectorCallback( $services )
		);
	},

	'WikibaseRepo.PropertyInfoBuilder' => function ( MediaWikiServices $services ): PropertyInfoBuilder {
		$settings = WikibaseRepo::getSettings( $services );
		$propertyIdMap = [];

		$formatterUrlProperty = $settings->getSetting( 'formatterUrlProperty' );
		if ( $formatterUrlProperty !== null ) {
			$propertyIdMap[PropertyInfoLookup::KEY_FORMATTER_URL] =
				new NumericPropertyId( $formatterUrlProperty );
		}

		$canonicalUriProperty = $settings->getSetting( 'canonicalUriProperty' );
		if ( $canonicalUriProperty !== null ) {
			$propertyIdMap[PropertyInfoStore::KEY_CANONICAL_URI] =
				new NumericPropertyId( $canonicalUriProperty );
		}

		return new PropertyInfoBuilder( $propertyIdMap );
	},

	'WikibaseRepo.PropertyTermsCollisionDetector' => function ( MediaWikiServices $services ): TermsCollisionDetector {
		return WikibaseRepo::getTermsCollisionDetectorFactory( $services )
			->getTermsCollisionDetector( Property::ENTITY_TYPE );
	},

	'WikibaseRepo.PropertyTermStoreWriter' => function ( MediaWikiServices $services ): EntityTermStoreWriter {
		if ( !in_array(
			Property::ENTITY_TYPE,
			WikibaseRepo::getLocalEntitySource( $services )->getEntityTypes()
		) ) {
			return new ThrowingEntityTermStoreWriter();
		}

		return new PropertyTermStoreWriterAdapter(
			WikibaseRepo::getTermStoreWriterFactory( $services )->newPropertyTermStoreWriter()
		);
	},

	'WikibaseRepo.PropertyValueExpertsModule' => function ( MediaWikiServices $services ): PropertyValueExpertsModule {
		return new PropertyValueExpertsModule( WikibaseRepo::getDataTypeDefinitions( $services ) );
	},

	'WikibaseRepo.RdfBuilderFactory' => function ( MediaWikiServices $services ): RdfBuilderFactory {
		return new RdfBuilderFactory(
			WikibaseRepo::getRdfVocabulary( $services ),
			WikibaseRepo::getEntityRdfBuilderFactory( $services ),
			WikibaseRepo::getEntityContentFactory( $services ),
			WikibaseRepo::getEntityStubRdfBuilderFactory( $services ),
			WikibaseRepo::getEntityRevisionLookup( $services )
		);
	},

	'WikibaseRepo.RdfVocabulary' => function ( MediaWikiServices $services ): RdfVocabulary {
		$repoSettings = WikibaseRepo::getSettings( $services );
		$languageCodes = array_merge(
			$services->getMainConfig()->get( 'DummyLanguageCodes' ),
			$repoSettings->getSetting( 'canonicalLanguageCodes' )
		);

		$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions( $services );
		$nodeNamespacePrefixes = $entitySourceDefinitions->getRdfNodeNamespacePrefixes();
		$predicateNamespacePrefixes = $entitySourceDefinitions->getRdfPredicateNamespacePrefixes();

		$urlProvider = new EntitySourceDocumentUrlProvider( $services->getTitleFactory() );
		$canonicalDocumentUrls = $urlProvider->getCanonicalDocumentsUrls( $entitySourceDefinitions );

		return new RdfVocabulary(
			$entitySourceDefinitions->getConceptBaseUris(),
			$canonicalDocumentUrls,
			$entitySourceDefinitions,
			$nodeNamespacePrefixes,
			$predicateNamespacePrefixes,
			$languageCodes,
			WikibaseRepo::getDataTypeDefinitions( $services )->getRdfTypeUris(),
			$repoSettings->getSetting( 'pagePropertiesRdf' ) ?: [],
			$repoSettings->getSetting( 'rdfDataRightsUrl' )
		);
	},

	'WikibaseRepo.RedirectResolvingLatestRevisionLookup' => function (
		MediaWikiServices $services
	): RedirectResolvingLatestRevisionLookup {
		return new RedirectResolvingLatestRevisionLookup( WikibaseRepo::getEntityRevisionLookup( $services ) );
	},

	'WikibaseRepo.ReferenceNormalizer' => function ( MediaWikiServices $services ): ReferenceNormalizer {
		return new ReferenceNormalizer( WikibaseRepo::getSnakNormalizer( $services ) );
	},

	'WikibaseRepo.RepoDomainDbFactory' => function ( MediaWikiServices $services ): RepoDomainDbFactory {
		$lbFactory = $services->getDBLoadBalancerFactory();

		return new RepoDomainDbFactory(
			$lbFactory,
			$lbFactory->getLocalDomainID(),
			[ DomainDb::LOAD_GROUP_FROM_REPO ]
		);
	},

	'WikibaseRepo.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getRepoSettings();
	},

	'WikibaseRepo.SiteLinkBadgeChangeOpSerializationValidator' => function (
		MediaWikiServices $services
	): SiteLinkBadgeChangeOpSerializationValidator {
		return new SiteLinkBadgeChangeOpSerializationValidator(
			WikibaseRepo::getEntityTitleLookup( $services ),
			array_keys(
				WikibaseRepo::getSettings( $services )
					->getSetting( 'badgeItems' )
			)
		);
	},

	'WikibaseRepo.SiteLinkGlobalIdentifiersProvider' => function (
		MediaWikiServices $services
	): SiteLinkGlobalIdentifiersProvider {
		$cacheSecret = hash( 'sha256', $services->getMainConfig()->get( 'SecretKey' ) );
		return new SiteLinkGlobalIdentifiersProvider(
			WikibaseRepo::getSiteLinkTargetProvider( $services ),
			new SimpleCacheWithBagOStuff(
				$services->getLocalServerObjectCache(),
				'wikibase.siteLinkGlobalIdentifiersProvider.',
				$cacheSecret
			)
		);
	},

	'WikibaseRepo.SiteLinkPageNormalizer' => function (
		MediaWikiServices $services
	): SiteLinkPageNormalizer {
		return new SiteLinkPageNormalizer(
			WikibaseRepo::getSettings( $services )->getSetting( 'redirectBadgeItems' )
		);
	},

	'WikibaseRepo.SiteLinkTargetProvider' => function (
		MediaWikiServices $services
	): SiteLinkTargetProvider {
		return new SiteLinkTargetProvider(
			$services->getSiteLookup(),
			WikibaseRepo::getSettings( $services )->getSetting( 'specialSiteLinkGroups' )
		);
	},

	'WikibaseRepo.SnakFactory' => function ( MediaWikiServices $services ): SnakFactory {
		return new SnakFactory(
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeFactory( $services ),
			WikibaseRepo::getDataValueFactory( $services )
		);
	},

	'WikibaseRepo.SnakFormatterFactory' => function ( MediaWikiServices $services ): OutputFormatSnakFormatterFactory {
		return new OutputFormatSnakFormatterFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )->getSnakFormatterFactoryCallbacks(),
			WikibaseRepo::getValueFormatterFactory( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeFactory( $services ),
			WikibaseRepo::getMessageInLanguageProvider( $services )
		);
	},

	'WikibaseRepo.SnakNormalizer' => function ( MediaWikiServices $services ): SnakNormalizer {
		return new SnakNormalizer(
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getLogger( $services ),
			WikibaseRepo::getDataTypeDefinitions( $services )
				->getNormalizerFactoryCallbacks()
		);
	},

	'WikibaseRepo.StatementGuidParser' => function ( MediaWikiServices $services ): StatementGuidParser {
		return new StatementGuidParser( WikibaseRepo::getEntityIdParser( $services ) );
	},

	'WikibaseRepo.StatementGuidValidator' => function ( MediaWikiServices $services ): StatementGuidValidator {
		return new StatementGuidValidator( WikibaseRepo::getEntityIdParser( $services ) );
	},

	'WikibaseRepo.StatementNormalizer' => function ( MediaWikiServices $services ): StatementNormalizer {
		return new StatementNormalizer(
			WikibaseRepo::getSnakNormalizer( $services ),
			WikibaseRepo::getReferenceNormalizer( $services )
		);
	},

	'WikibaseRepo.StorageEntitySerializer' => function ( MediaWikiServices $services ): Serializer {
		$serializerFactoryCallbacks = WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::STORAGE_SERIALIZER_FACTORY_CALLBACK );
		$baseSerializerFactory = WikibaseRepo::getBaseDataModelSerializerFactory( $services );
		$serializers = [];

		foreach ( $serializerFactoryCallbacks as $callback ) {
			$serializers[] = $callback( $baseSerializerFactory );
		}

		return new DispatchingSerializer( $serializers );
	},

	'WikibaseRepo.Store' => function ( MediaWikiServices $services ): Store {
		// TODO: the idea of local entity source seems not really suitable here. Store should probably
		// get source definitions and pass the right source/sources to services it creates accordingly
		// (as long as what it creates should not migrate to *SourceServices in the first place)
		return new SqlStore(
			WikibaseRepo::getEntityChangeFactory( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityIdComposer( $services ),
			WikibaseRepo::getEntityIdLookup( $services ),
			WikibaseRepo::getEntityTitleStoreLookup( $services ),
			WikibaseRepo::getEntityNamespaceLookup( $services ),
			WikibaseRepo::getIdGenerator( $services ),
			WikibaseRepo::getWikibaseServices( $services ),
			WikibaseRepo::getLocalEntitySource( $services ),
			WikibaseRepo::getSettings( $services )
		);
	},

	'WikibaseRepo.StringNormalizer' => function ( MediaWikiServices $services ): StringNormalizer {
		return new StringNormalizer();
	},

	'WikibaseRepo.StringValueNormalizer' => function ( MediaWikiServices $services ): StringValueNormalizer {
		return new StringValueNormalizer(
			WikibaseRepo::getStringNormalizer( $services ),
			WikibaseRepo::getLogger( $services )
		);
	},

	'WikibaseRepo.SubEntityTypesMapper' => function ( MediaWikiServices $services ): SubEntityTypesMapper {
		return new SubEntityTypesMapper( WikibaseRepo::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SUB_ENTITY_TYPES ) );
	},

	'WikibaseRepo.SummaryFormatter' => function ( MediaWikiServices $services ): SummaryFormatter {
		// This needs to use an EntityIdPlainLinkFormatter as we want to mangle
		// the links created in HtmlPageLinkRendererEndHookHandler afterwards (the links must not
		// contain a display text: [[Item:Q1]] is fine but [[Item:Q1|Q1]] isn't).
		$idFormatter = new EntityIdPlainLinkFormatter( WikibaseRepo::getEntityTitleLookup( $services ) );

		$formatterFactoryCBs = WikibaseRepo::getDataTypeDefinitions( $services )
			->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE );

		// Iterate through all defined entity types and override the formatter for entity IDs.
		foreach ( WikibaseRepo::getEntityTypeDefinitions( $services )->getEntityTypes() as $entityType ) {
			$formatterFactoryCBs[ "PT:wikibase-$entityType" ] = function (
				$format,
				FormatterOptions $options ) use ( $idFormatter ) {
				if ( $format === SnakFormatter::FORMAT_PLAIN ) {
					return new EntityIdValueFormatter( $idFormatter );
				} else {
					return null;
				}
			};
		}

		$contentLanguage = $services->getContentLanguage();

		// Create a new ValueFormatterFactory from entity definition overrides.
		$valueFormatterFactory = new OutputFormatValueFormatterFactory(
			$formatterFactoryCBs,
			$contentLanguage,
			WikibaseRepo::getLanguageFallbackChainFactory( $services )
		);

		// Create a new SnakFormatterFactory based on the specialized ValueFormatterFactory.
		$snakFormatterFactory = new OutputFormatSnakFormatterFactory(
			[], // XXX: do we want DataTypeDefinitions::getSnakFormatterFactoryCallbacks()
			$valueFormatterFactory,
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeFactory( $services ),
			WikibaseRepo::getMessageInLanguageProvider( $services )
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

		return new SummaryFormatter(
			$idFormatter,
			$valueFormatter,
			$snakFormatter,
			$contentLanguage,
			WikibaseRepo::getEntityIdParser( $services )
		);
	},

	'WikibaseRepo.TermBuffer' => function ( MediaWikiServices $services ): TermBuffer {
		return WikibaseRepo::getPrefetchingTermLookup( $services );
	},

	'WikibaseRepo.TermFallbackCache' => function ( MediaWikiServices $services ): TermFallbackCacheFacade {
		return new TermFallbackCacheFacade(
			WikibaseRepo::getTermFallbackCacheFactory( $services )->getTermFallbackCache(),
			WikibaseRepo::getSettings( $services )->getSetting( 'sharedCacheDuration' )
		);
	},

	'WikibaseRepo.TermFallbackCacheFactory' => function ( MediaWikiServices $services ): TermFallbackCacheFactory {
		$settings = WikibaseRepo::getSettings( $services );
		return new TermFallbackCacheFactory(
			$settings->getSetting( 'sharedCacheType' ),
			WikibaseRepo::getLogger( $services ),
			$services->getStatsdDataFactory(),
			hash( 'sha256', $services->getMainConfig()->get( 'SecretKey' ) ),
			new TermFallbackCacheServiceFactory(),
			$settings->getSetting( 'termFallbackCacheVersion' )
		);
	},

	'WikibaseRepo.TermInLangIdsResolverFactory' => function (
		MediaWikiServices $services
	): TermInLangIdsResolverFactory {
		return new TermInLangIdsResolverFactory(
			WikibaseRepo::getRepoDomainDbFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			$services->getMainWANObjectCache()
		);
	},

	'WikibaseRepo.TermLookup' => function ( MediaWikiServices $services ): TermLookup {
		return WikibaseRepo::getPrefetchingTermLookup( $services );
	},

	'WikibaseRepo.TermsCollisionDetectorFactory' => function ( MediaWikiServices $services ): TermsCollisionDetectorFactory {
		return new TermsCollisionDetectorFactory(
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb(),
			WikibaseRepo::getTypeIdsLookup( $services )
		);
	},

	'WikibaseRepo.TermsLanguages' => function ( MediaWikiServices $services ): ContentLanguages {
		return WikibaseRepo::getWikibaseContentLanguages( $services )
			->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
	},

	'WikibaseRepo.TermStoreWriterFactory' => function ( MediaWikiServices $services ): TermStoreWriterFactory {
		return new TermStoreWriterFactory(
			WikibaseRepo::getLocalEntitySource( $services ),
			WikibaseRepo::getStringNormalizer( $services ),
			WikibaseRepo::getTypeIdsAcquirer( $services ),
			WikibaseRepo::getTypeIdsLookup( $services ),
			WikibaseRepo::getTypeIdsResolver( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb(),
			$services->getMainWANObjectCache(),
			$services->getJobQueueGroup(),
			WikibaseRepo::getLogger( $services )
		);
	},

	'WikibaseRepo.TermValidatorFactory' => function ( MediaWikiServices $services ): TermValidatorFactory {
		$settings = WikibaseRepo::getSettings( $services );

		// Use the old deprecated setting if it exists
		if ( $settings->hasSetting( 'multilang-limits' ) ) {
			$constraints = $settings->getSetting( 'multilang-limits' );
		} else {
			$constraints = $settings->getSetting( 'string-limits' )['multilang'];
		}

		$maxLength = $constraints['length'];

		$languages = WikibaseRepo::getTermsLanguages( $services )->getLanguages();

		return new TermValidatorFactory(
			$maxLength,
			$languages,
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getTermsCollisionDetectorFactory( $services ),
			WikibaseRepo::getTermLookup( $services ),
			$services->getLanguageNameUtils()
		);
	},

	'WikibaseRepo.TokenCheckInteractor' => function ( MediaWikiServices $services ): TokenCheckInteractor {
		return new TokenCheckInteractor();
	},

	'WikibaseRepo.TypeIdsAcquirer' => function ( MediaWikiServices $services ): TypeIdsAcquirer {
		return WikibaseRepo::getDatabaseTypeIdsStore( $services );
	},

	'WikibaseRepo.TypeIdsLookup' => function ( MediaWikiServices $services ): TypeIdsLookup {
		return WikibaseRepo::getDatabaseTypeIdsStore( $services );
	},

	'WikibaseRepo.TypeIdsResolver' => function ( MediaWikiServices $services ): TypeIdsResolver {
		return WikibaseRepo::getDatabaseTypeIdsStore( $services );
	},

	'WikibaseRepo.UnitConverter' => function ( MediaWikiServices $services ): ?UnitConverter {
		$settings = WikibaseRepo::getSettings( $services );
		if ( !$settings->hasSetting( 'unitStorage' ) ) {
			return null;
		}

		// Creates configured unit storage.
		$unitStorage = ObjectFactory::getObjectFromSpec( $settings->getSetting( 'unitStorage' ) );
		if ( !( $unitStorage instanceof UnitStorage ) ) {
			wfWarn( "Bad unit storage configuration, ignoring" );
			return null;
		}
		return new UnitConverter( $unitStorage, WikibaseRepo::getItemVocabularyBaseUri( $services ) );
	},

	'WikibaseRepo.UserLanguage' => function ( MediaWikiServices $services ): Language {
		global $wgLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: The $wgLang global may still be null when the SetupAfterCache hook is
		// run during bootstrapping.

		if ( !$wgLang ) {
			throw new MWException( 'Premature access: $wgLang is not yet initialized!' );
		}

		StubObject::unstub( $wgLang );
		return $wgLang;
	},

	'WikibaseRepo.ValidatorErrorLocalizer' => function ( MediaWikiServices $services ): ValidatorErrorLocalizer {
		$formatter = WikibaseRepo::getMessageParameterFormatter( $services );
		return new ValidatorErrorLocalizer( $formatter );
	},

	'WikibaseRepo.ValueFormatterFactory' => function ( MediaWikiServices $services ): OutputFormatValueFormatterFactory {
		$formatterFactoryCBs = WikibaseRepo::getDataTypeDefinitions( $services )
			->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE );

		return new OutputFormatValueFormatterFactory(
			$formatterFactoryCBs,
			$services->getContentLanguage(),
			WikibaseRepo::getLanguageFallbackChainFactory( $services )
		);
	},

	'WikibaseRepo.ValueParserFactory' => function ( MediaWikiServices $services ): ValueParserFactory {
		$dataTypeDefinitions = WikibaseRepo::getDataTypeDefinitions( $services );
		$callbacks = $dataTypeDefinitions->getParserFactoryCallbacks();

		// For backwards-compatibility, also register parsers under legacy names,
		// for use with the deprecated 'parser' parameter of the wbparsevalue API module.
		$prefixedCallbacks = $dataTypeDefinitions->getParserFactoryCallbacks(
			DataTypeDefinitions::PREFIXED_MODE
		);
		if ( isset( $prefixedCallbacks['VT:wikibase-entityid'] ) ) {
			$callbacks['wikibase-entityid'] = $prefixedCallbacks['VT:wikibase-entityid'];
		}
		if ( isset( $prefixedCallbacks['VT:globecoordinate'] ) ) {
			$callbacks['globecoordinate'] = $prefixedCallbacks['VT:globecoordinate'];
		}
		// 'null' is not a datatype. Kept for backwards compatibility.
		$callbacks['null'] = function () {
			return new NullParser();
		};

		return new ValueParserFactory( $callbacks );
	},

	'WikibaseRepo.ValueSnakRdfBuilderFactory' => function ( MediaWikiServices $services ): ValueSnakRdfBuilderFactory {
		return new ValueSnakRdfBuilderFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )
				->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			WikibaseRepo::getLogger( $services )
		);
	},

	'WikibaseRepo.ViewFactory' => function ( MediaWikiServices $services ): ViewFactory {
		$settings = WikibaseRepo::getSettings( $services );

		$statementGrouperBuilder = new StatementGrouperBuilder(
			$settings->getSetting( 'statementSections' ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getStatementGuidParser( $services )
		);

		$propertyOrderProvider = new CachingPropertyOrderProvider(
			new WikiPagePropertyOrderProvider(
				$services->getWikiPageFactory(),
				$services->getTitleFactory()
					->newFromText( 'MediaWiki:Wikibase-SortedProperties' )
			),
			ObjectCache::getLocalClusterInstance()
		);

		return new ViewFactory(
			WikibaseRepo::getEntityIdHtmlLinkFormatterFactory( $services ),
			WikibaseRepo::getEntityIdLabelFormatterFactory( $services ),
			new WikibaseHtmlSnakFormatterFactory(
				WikibaseRepo::getSnakFormatterFactory( $services )
			),
			$statementGrouperBuilder->getStatementGrouper(),
			$propertyOrderProvider,
			$services->getSiteLookup(),
			WikibaseRepo::getDataTypeFactory( $services ),
			TemplateFactory::getDefaultInstance(),
			WikibaseRepo::getLanguageNameLookupFactory( $services ),
			WikibaseRepo::getLanguageDirectionalityLookup( $services ),
			WikibaseRepo::getNumberLocalizerFactory( $services ),
			$settings->getSetting( 'siteLinkGroups' ),
			$settings->getSetting( 'specialSiteLinkGroups' ),
			$settings->getSetting( 'badgeItems' ),
			WikibaseRepo::getLocalizedTextProviderFactory( $services ),
			new RepoSpecialPageLinker(),
			$services->getLanguageFactory()
		);
	},

	'WikibaseRepo.WikibaseContentLanguages' => function ( MediaWikiServices $services ): WikibaseContentLanguages {
		return WikibaseContentLanguages::getDefaultInstance(
			$services->getHookContainer(),
			$services->getLanguageNameUtils()
		);
	},

	'WikibaseRepo.WikibaseServices' => function ( MediaWikiServices $services ): WikibaseServices {
		$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions( $services );
		$singleEntitySourceServicesFactory = new SingleEntitySourceServicesFactory(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityIdComposer( $services ),
			WikibaseRepo::getDataValueDeserializer( $services ),
			$services->getNameTableStoreFactory(),
			WikibaseRepo::getDataAccessSettings( $services ),
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getStorageEntitySerializer( $services ),
			WikibaseRepo::getEntityTypeDefinitions( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services )
		);

		$singleSourceServices = [];

		foreach ( $entitySourceDefinitions->getSources() as $source ) {
			if ( $source->getType() === ApiEntitySource::TYPE ) {
				continue;
			}
			$singleSourceServices[$source->getSourceName()] = $singleEntitySourceServicesFactory
				->getServicesForSource( $source );
		}
		return new MultipleEntitySourceServices(
			$entitySourceDefinitions,
			$singleSourceServices
		);
	},

];
