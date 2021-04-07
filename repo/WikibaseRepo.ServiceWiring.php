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
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueParsers\NullParser;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataAccess\ByTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\MediaWiki\EntitySourceDocumentUrlProvider;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\EntityIdLinkFormatter;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Modules\PropertyValueExpertsModule;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\ItemTermStoreWriterAdapter;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\PropertyTermStoreWriterAdapter;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\ThrowingEntityTermStoreWriter;
use Wikibase\Lib\Store\TitleLookupBasedEntityArticleIdLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityRedirectChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup;
use Wikibase\Lib\Store\TypeDispatchingArticleIdLookup;
use Wikibase\Lib\Store\TypeDispatchingExistenceChecker;
use Wikibase\Lib\Store\TypeDispatchingRedirectChecker;
use Wikibase\Lib\Store\TypeDispatchingTitleTextLookup;
use Wikibase\Lib\Store\TypeDispatchingUrlLookup;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\Units\UnitStorage;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\EntitySourceDefinitionsLegacyRepoSettingsParser;
use Wikibase\Repo\EntityTypeDefinitionsFedPropsOverrider;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntitySourceDefinitionsConfigParser;
use Wikibase\Repo\Localizer\MessageParameterFormatter;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;
use Wikibase\Repo\Notifications\HookChangeTransmitter;
use Wikibase\Repo\Notifications\RepoEntityChange;
use Wikibase\Repo\Notifications\RepoItemChange;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\Repo\Store\Sql\SqlStore;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ObjectFactory;

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

	'WikibaseRepo.BaseDataModelDeserializerFactory' => function ( MediaWikiServices $services ): DeserializerFactory {
		return new DeserializerFactory(
			WikibaseRepo::getDataValueDeserializer( $services ),
			WikibaseRepo::getEntityIdParser( $services )
		);
	},

	'WikibaseRepo.BaseDataModelSerializerFactory' => function ( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT );
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
			$changeStore = WikibaseRepo::getStore( $services )->getChangeStore();
			$transmitters[] = new DatabaseChangeTransmitter( $changeStore );
		}

		return new ChangeNotifier(
			WikibaseRepo::getEntityChangeFactory( $services ),
			$transmitters,
			CentralIdLookup::factoryNonLocal() // TODO inject (T265767)
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
			$services->getDBLoadBalancer(),
			$services->getMainWANObjectCache()
		);
	},

	'WikibaseRepo.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

		$dataTypes = array_merge_recursive( $baseDataTypes, $repoDataTypes );

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
			'wikibase-entityid' => function ( $value ) use ( $services ) {
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

	'WikibaseRepo.EntityArticleIdLookup' => function ( MediaWikiServices $services ): EntityArticleIdLookup {
		return new TypeDispatchingArticleIdLookup(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK ),
			new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup( $services )
			)
		);
	},

	'WikibaseRepo.EntityChangeFactory' => function ( MediaWikiServices $services ): EntityChangeFactory {
		//TODO: take this from a setting or registry.
		$changeClasses = [
			Item::ENTITY_TYPE => RepoItemChange::class,
			// Other types of entities will use EntityChange
		];

		return new EntityChangeFactory(
			WikibaseRepo::getEntityDiffer( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			$changeClasses,
			RepoEntityChange::class,
			WikibaseRepo::getLogger( $services )
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
			new SqlSiteLinkConflictLookup( WikibaseRepo::getEntityIdComposer( $services ) )
		);
	},

	'WikibaseRepo.EntityContentFactory' => function ( MediaWikiServices $services ): EntityContentFactory {
		return new EntityContentFactory(
			WikibaseRepo::getContentModelMappings( $services ),
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK ),
			WikibaseRepo::getEntitySourceDefinitions( $services ),
			WikibaseRepo::getLocalEntitySource( $services ),
			$services->getInterwikiLookup()
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

	'WikibaseRepo.EntityExistenceChecker' => function ( MediaWikiServices $services ): EntityExistenceChecker {
		return new TypeDispatchingExistenceChecker(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::EXISTENCE_CHECKER_CALLBACK ),
			new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
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

	'WikibaseRepo.EntityIdLookup' => function ( MediaWikiServices $services ): EntityIdLookup {
		return WikibaseRepo::getEntityContentFactory( $services );
	},

	'WikibaseRepo.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		return new DispatchingEntityIdParser(
			WikibaseRepo::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);
	},

	'WikibaseRepo.EntityLookup' => function ( MediaWikiServices $services ): EntityLookup {
		return WikibaseRepo::getStore( $services )
			->getEntityLookup(
				Store::LOOKUP_CACHING_ENABLED,
				LookupConstants::LATEST_FROM_REPLICA
			);
	},

	'WikibaseRepo.EntityNamespaceLookup' => function ( MediaWikiServices $services ): EntityNamespaceLookup {
		return array_reduce(
			WikibaseRepo::getEntitySourceDefinitions( $services )->getSources(),
			function ( EntityNamespaceLookup $nsLookup, EntitySource $source ): EntityNamespaceLookup {
				return $nsLookup->merge( new EntityNamespaceLookup(
					$source->getEntityNamespaceIds(),
					$source->getEntitySlotNames()
				) );
			},
			new EntityNamespaceLookup( [], [] )
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
			$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_BUILDER_FACTORY_CALLBACK ),
			$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES )
		);
	},

	'WikibaseRepo.EntityRedirectChecker' => function ( MediaWikiServices $services ): EntityRedirectChecker {
		return new TypeDispatchingRedirectChecker(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::REDIRECT_CHECKER_CALLBACK ),
			new TitleLookupBasedEntityRedirectChecker(
				WikibaseRepo::getEntityTitleLookup( $services )
			)
		);
	},

	'WikibaseRepo.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseRepo::getSettings( $services );
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );

		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray(
				$settings->getSetting( 'entitySources' ),
				$entityTypeDefinitions
			);
		}

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		if ( $settings->getSetting( 'federatedPropertiesEnabled' ) ) {
			$configParser = new FederatedPropertiesEntitySourceDefinitionsConfigParser( $settings );

			return $configParser->initializeDefaults(
				$parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions ),
				$entityTypeDefinitions
			);
		}

		return $parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions );
	},

	'WikibaseRepo.EntityStore' => function ( MediaWikiServices $services ): EntityStore {
		return WikibaseRepo::getStore( $services )->getEntityStore();
	},

	'WikibaseRepo.EntityStoreWatcher' => function ( MediaWikiServices $services ): EntityStoreWatcher {
		return WikibaseRepo::getStore( $services )->getEntityStoreWatcher();
	},

	'WikibaseRepo.EntityTitleLookup' => function ( MediaWikiServices $services ): EntityTitleLookup {
		return WikibaseRepo::getEntityTitleStoreLookup( $services );
	},

	'WikibaseRepo.EntityTitleStoreLookup' => function ( MediaWikiServices $services ): EntityTitleStoreLookup {
		return new TypeDispatchingEntityTitleStoreLookup(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_TITLE_STORE_LOOKUP_FACTORY_CALLBACK ),
			WikibaseRepo::getEntityContentFactory( $services )
		);
	},

	'WikibaseRepo.EntityTitleTextLookup' => function ( MediaWikiServices $services ): EntityTitleTextLookup {
		return new TypeDispatchingTitleTextLookup(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::TITLE_TEXT_LOOKUP_CALLBACK ),
			new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup( $services )
			)
		);
	},

	'WikibaseRepo.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

		$entityTypes = array_merge_recursive( $baseEntityTypes, $repoEntityTypes );

		$services->getHookContainer()->run( 'WikibaseRepoEntityTypes', [ &$entityTypes ] );

		$settings = WikibaseRepo::getSettings( $services );
		$overrider = EntityTypeDefinitionsFedPropsOverrider::factory( $settings->getSetting( 'federatedPropertiesEnabled' ) );

		$entityTypes = $overrider->override( $entityTypes );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseRepo.EntityUrlLookup' => function ( MediaWikiServices $services ): EntityUrlLookup {
		return new TypeDispatchingUrlLookup(
			WikibaseRepo::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::URL_LOOKUP_CALLBACK ),
			new TitleLookupBasedEntityUrlLookup(
				WikibaseRepo::getEntityTitleLookup( $services )
			)
		);
	},

	'WikibaseRepo.ExternalFormatStatementDeserializer' => function ( MediaWikiServices $services ): Deserializer {
		return WikibaseRepo::getBaseDataModelDeserializerFactory( $services )->newStatementDeserializer();
	},

	'WikibaseRepo.IdGenerator' => function ( MediaWikiServices $services ): IdGenerator {
		$settings = WikibaseRepo::getSettings( $services );

		switch ( $settings->getSetting( 'idGenerator' ) ) {
			case 'original':
				$idGenerator = new SqlIdGenerator(
					$services->getDBLoadBalancer(),
					$settings->getSetting( 'reservedIds' ),
					$settings->getSetting( 'idGeneratorSeparateDbConnection' )
				);
				break;
			case 'mysql-upsert':
				// We could make sure the 'upsert' generator is only being used with mysql dbs here,
				// but perhaps that is an unnecessary check? People will realize when the DB query for
				// ID selection fails anyway...
				$idGenerator = new UpsertSqlIdGenerator(
					$services->getDBLoadBalancer(),
					$settings->getSetting( 'reservedIds' ),
					$settings->getSetting( 'idGeneratorSeparateDbConnection' )
				);
				break;
			default:
				throw new InvalidArgumentException(
					'idGenerator config option must be either \'original\' or \'mysql-upsert\''
				);
		}

		if ( $settings->getSetting( 'idGeneratorRateLimiting' ) ) {
			$idGenerator = new RateLimitingIdGenerator(
				$idGenerator,
				RequestContext::getMain()
			);
		}

		return $idGenerator;
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
			->getSourceForEntityType( Item::ENTITY_TYPE );

		if ( $itemSource === null ) {
			throw new LogicException( 'No source providing Items configured!' );
		}

		return $itemSource->getConceptBaseUri();
	},

	'WikibaseRepo.KartographerEmbeddingHandler' => function ( MediaWikiServices $services ): ?CachingKartographerEmbeddingHandler {
		$settings = WikibaseRepo::getSettings( $services );
		$config = $services->getMainConfig();
		if (
			$settings->getSetting( 'useKartographerGlobeCoordinateFormatter' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' ) &&
			$config->has( 'KartographerEnableMapFrame' ) &&
			$config->get( 'KartographerEnableMapFrame' )
		) {
			return new CachingKartographerEmbeddingHandler(
				$services->getParserFactory()->create()
			);
		} else {
			return null;
		}
	},

	'WikibaseRepo.LanguageFallbackChainFactory' => function ( MediaWikiServices $services ): LanguageFallbackChainFactory {
		return new LanguageFallbackChainFactory(
			$services->getLanguageFactory(),
			$services->getLanguageConverterFactory(),
			$services->getLanguageFallback()
		);
	},

	'WikibaseRepo.LanguageFallbackLabelDescriptionLookupFactory' => function (
		MediaWikiServices $services
	): LanguageFallbackLabelDescriptionLookupFactory {
		return new LanguageFallbackLabelDescriptionLookupFactory(
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getTermLookup( $services ),
			WikibaseRepo::getTermBuffer( $services )
		);
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

	'WikibaseRepo.LocalRepoWikiPageMetaDataAccessor' => function ( MediaWikiServices $services ): WikiPageEntityMetaDataAccessor {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup( $services );
		$repoName = ''; // Empty string here means this only works for the local repo
		$dbName = false; // false means the local database
		$logger = WikibaseRepo::getLogger( $services );

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
					WikibaseRepo::getLocalEntitySource( $services ),
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

	'WikibaseRepo.PrefetchingTermLookup' => function ( MediaWikiServices $services ): PrefetchingTermLookup {
		$entitySourceDefinitions = WikibaseRepo::getEntitySourceDefinitions( $services );
		$lookupFactory = WikibaseRepo::getPrefetchingTermLookupFactory( $services );

		$lookups = array_map(
			[ $lookupFactory, 'getLookupForSource' ],
			$entitySourceDefinitions->getEntityTypeToSourceMapping()
		);

		return new ByTypeDispatchingPrefetchingTermLookup( $lookups );
	},

	'WikibaseRepo.PrefetchingTermLookupFactory' => function ( MediaWikiServices $services ): PrefetchingTermLookupFactory {
		return new PrefetchingTermLookupFactory(
			WikibaseRepo::getEntitySourceDefinitions( $services ),
			WikibaseRepo::getEntityTypeDefinitions( $services ),
			WikibaseRepo::getSingleEntitySourceServicesFactory( $services )
		);
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

	'WikibaseRepo.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getRepoSettings();
	},

	// TODO: This service is just a convenience service to simplify the transition away from SingleEntitySourceServices,
	// 		 and thus should eventually be removed. See T277731.
	'WikibaseRepo.SingleEntitySourceServicesFactory' => function (
		MediaWikiServices $services
	): SingleEntitySourceServicesFactory {
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
		return new SingleEntitySourceServicesFactory(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityIdComposer( $services ),
			WikibaseRepo::getDataValueDeserializer( $services ),
			$services->getNameTableStoreFactory(),
			WikibaseRepo::getDataAccessSettings( $services ),
			WikibaseRepo::getLanguageFallbackChainFactory( $services ),
			WikibaseRepo::getStorageEntitySerializer( $services ),
			$entityTypeDefinitions
		);
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

	'WikibaseRepo.StatementGuidParser' => function ( MediaWikiServices $services ): StatementGuidParser {
		return new StatementGuidParser( WikibaseRepo::getEntityIdParser( $services ) );
	},

	'WikibaseRepo.StatementGuidValidator' => function ( MediaWikiServices $services ): StatementGuidValidator {
		return new StatementGuidValidator( WikibaseRepo::getEntityIdParser( $services ) );
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

	'WikibaseRepo.TermLookup' => function ( MediaWikiServices $services ): TermLookup {
		return WikibaseRepo::getPrefetchingTermLookup( $services );
	},

	'WikibaseRepo.TermsCollisionDetectorFactory' => function ( MediaWikiServices $services ): TermsCollisionDetectorFactory {
		return new TermsCollisionDetectorFactory(
			$services->getDBLoadBalancer(),
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
			$services->getDBLoadBalancerFactory(),
			$services->getMainWANObjectCache(),
			JobQueueGroup::singleton(),
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
			WikibaseRepo::getTermLookup( $services )
		);
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
		return new UnitConverter( $unitStorage, $settings->getSetting( 'conceptBaseUri' ) );
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
		$callbacks['null'] = function() {
			return new NullParser();
		};

		return new ValueParserFactory( $callbacks );
	},

	'WikibaseRepo.ValueSnakRdfBuilderFactory' => function ( MediaWikiServices $services ): ValueSnakRdfBuilderFactory {
		return new ValueSnakRdfBuilderFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )
				->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
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
		$singleEntitySourceServicesFactory = WikibaseRepo::getSingleEntitySourceServicesFactory( $services );

		$singleSourceServices = [];
		foreach ( $entitySourceDefinitions->getSources() as $source ) {
			$singleSourceServices[$source->getSourceName()] = $singleEntitySourceServicesFactory
				->getServicesForSource( $source );
		}
		return new MultipleEntitySourceServices(
			$entitySourceDefinitions,
			$singleSourceServices
		);
	},

];
