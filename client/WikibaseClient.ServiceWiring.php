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
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\Client\CachingOtherProjectsSitesProvider;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\DataAccess\ClientSiteLinkTitleLookup;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\OtherProjectsSitesGenerator;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataAccess\ByTypeDispatchingEntityIdLookup;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataAccess\SourceAndTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\DisabledEntityTypesEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\Reference\WellKnownReferenceProperties;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\MediaWikiMessageInLanguageProvider;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityRedirectTargetLookup;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\Sql\Terms\CachedDatabasePropertyLabelResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityRedirectChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\WikibaseSettings;

/** @phpcs-require-sorted-array */
return [

	'WikibaseClient.AffectedPagesFinder' => function ( MediaWikiServices $services ): AffectedPagesFinder {
		return new AffectedPagesFinder(
			WikibaseClient::getStore( $services )->getUsageLookup(),
			$services->getTitleFactory(),
			$services->getPageStore(),
			$services->getLinkBatchFactory(),
			WikibaseClient::getSettings( $services )->getSetting( 'siteGlobalID' ),
			WikibaseClient::getLogger( $services )
		);
	},

	'WikibaseClient.AliasTermBuffer' => function ( MediaWikiServices $services ): AliasTermBuffer {
		return WikibaseClient::getPrefetchingTermLookup( $services );
	},

	'WikibaseClient.BaseDataModelDeserializerFactory' => function ( MediaWikiServices $services ): DeserializerFactory {
		return new DeserializerFactory(
			WikibaseClient::getDataValueDeserializer( $services ),
			WikibaseClient::getEntityIdParser( $services )
		);
	},

	'WikibaseClient.ChangeHandler' => function ( MediaWikiServices $services ): ChangeHandler {
		$logger = WikibaseClient::getLogger( $services );

		$pageUpdater = new WikiPageUpdater(
			$services->getJobQueueGroup(),
			$logger,
			$services->getStatsdDataFactory()
		);

		$settings = WikibaseClient::getSettings( $services );

		$pageUpdater->setPurgeCacheBatchSize( $settings->getSetting( 'purgeCacheBatchSize' ) );
		$pageUpdater->setRecentChangesBatchSize( $settings->getSetting( 'recentChangesBatchSize' ) );

		$changeListTransformer = new ChangeRunCoalescer(
			WikibaseClient::getEntityRevisionLookup( $services ),
			WikibaseClient::getEntityChangeFactory( $services ),
			$logger,
			$settings->getSetting( 'siteGlobalID' )
		);

		return new ChangeHandler(
			WikibaseClient::getAffectedPagesFinder( $services ),
			$services->getTitleFactory(),
			$services->getPageStore(),
			$pageUpdater,
			$changeListTransformer,
			$logger,
			WikibaseClient::getHookRunner( $services ),
			$settings->getSetting( 'injectRecentChanges' )
		);
	},

	'WikibaseClient.ClientDomainDbFactory' => function( MediaWikiServices $services ): ClientDomainDbFactory {
		$lbFactory = $services->getDBLoadBalancerFactory();

		return new ClientDomainDbFactory(
			$lbFactory,
			[ DomainDb::LOAD_GROUP_FROM_CLIENT ]
		);
	},

	'WikibaseClient.CompactBaseDataModelSerializerFactory' => function ( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
	},

	'WikibaseClient.CompactEntitySerializer' => function ( MediaWikiServices $services ): Serializer {
		$serializerFactoryCallbacks = WikibaseClient::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK );
		$baseSerializerFactory = WikibaseClient::getCompactBaseDataModelSerializerFactory( $services );
		$serializers = [];

		foreach ( $serializerFactoryCallbacks as $callback ) {
			$serializers[] = $callback( $baseSerializerFactory );
		}

		return new DispatchingSerializer( $serializers );
	},

	'WikibaseClient.DataAccessSettings' => function ( MediaWikiServices $services ): DataAccessSettings {
		return new DataAccessSettings(
			WikibaseClient::getSettings( $services )->getSetting( 'maxSerializedEntitySize' )
		);
	},

	'WikibaseClient.DataAccessSnakFormatterFactory' => function ( MediaWikiServices $services ): DataAccessSnakFormatterFactory {
		return new DataAccessSnakFormatterFactory(
			WikibaseClient::getLanguageFallbackChainFactory( $services ),
			WikibaseClient::getSnakFormatterFactory( $services ),
			WikibaseClient::getPropertyDataTypeLookup( $services ),
			WikibaseClient::getRepoItemUriParser( $services ),
			WikibaseClient::getFallbackLabelDescriptionLookupFactory( $services ),
			Wikibaseclient::getSettings( $services )->getSetting( 'allowDataAccessInUserLanguage' )
		);
	},

	'WikibaseClient.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$clientDataTypes = require __DIR__ . '/WikibaseClient.datatypes.php';

		$dataTypes = wfArrayPlus2d(
			$clientDataTypes,
			$baseDataTypes
		);

		$services->getHookContainer()->run( 'WikibaseClientDataTypes', [ &$dataTypes ] );

		$settings = WikibaseClient::getSettings( $services );

		return new DataTypeDefinitions(
			$dataTypes,
			$settings->getSetting( 'disabledDataTypes' )
		);
	},

	'WikibaseClient.DataTypeFactory' => function ( MediaWikiServices $services ): DataTypeFactory {
		return new DataTypeFactory(
			WikibaseClient::getDataTypeDefinitions( $services )->getValueTypes()
		);
	},

	'WikibaseClient.DataValueDeserializer' => function ( MediaWikiServices $services ): DataValueDeserializer {
		return new DataValueDeserializer( [
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function ( $value ) use ( $services ) {
				return isset( $value['id'] )
					? new EntityIdValue( WikibaseClient::getEntityIdParser( $services )->parse( $value['id'] ) )
					: EntityIdValue::newFromArray( $value );
			},
		] );
	},

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with {@link WikibaseClient::getDefaultSnakFormatterBuilders()} during bootstrap only!
	 * Program logic should use {@link WikibaseClient::getSnakFormatterFactory()} instead!
	 */
	'WikibaseClient.DefaultSnakFormatterBuilders' => function ( MediaWikiServices $services ): WikibaseSnakFormatterBuilders {
		return new WikibaseSnakFormatterBuilders(
			WikibaseClient::getDefaultValueFormatterBuilders( $services ),
			WikibaseClient::getStore( $services )->getPropertyInfoLookup(),
			WikibaseClient::getPropertyDataTypeLookup( $services ),
			WikibaseClient::getDataTypeFactory( $services )
		);
	},

	'WikibaseClient.DefaultValueFormatterBuilders' => function ( MediaWikiServices $services ): WikibaseValueFormatterBuilders {
		$clientStore = WikibaseClient::getStore( $services );
		$settings = WikibaseClient::getSettings( $services );
		$entityTitleLookup = new ClientSiteLinkTitleLookup(
			$clientStore->getSiteLinkLookup(),
			$settings->getSetting( 'siteGlobalID' )
		);
		$termFallbackCache = WikibaseClient::getTermFallbackCache( $services );
		$redirectResolvingLatestRevisionLookup = WikibaseClient::getRedirectResolvingLatestRevisionLookup( $services );
		$languageNameLookupFactory = new LanguageNameLookupFactory( $services->getLanguageNameUtils() );

		return new WikibaseValueFormatterBuilders(
			new FormatterLabelDescriptionLookupFactory(
				WikibaseClient::getTermLookup( $services ),
				$termFallbackCache,
				$redirectResolvingLatestRevisionLookup
			),
			$languageNameLookupFactory->getForLanguage( WikibaseClient::getUserLanguage( $services ) ),
			WikibaseClient::getRepoItemUriParser( $services ),
			$settings->getSetting( 'geoShapeStorageBaseUrl' ),
			$settings->getSetting( 'tabularDataStorageBaseUrl' ),
			$termFallbackCache,
			WikibaseClient::getEntityLookup( $services ),
			$redirectResolvingLatestRevisionLookup,
			$settings->getSetting( 'entitySchemaNamespace' ),
			new TitleLookupBasedEntityExistenceChecker(
				$entityTitleLookup,
				$services->getLinkBatchFactory()
			),
			new TitleLookupBasedEntityTitleTextLookup( $entityTitleLookup ),
			new TitleLookupBasedEntityUrlLookup( $entityTitleLookup ),
			new TitleLookupBasedEntityRedirectChecker( $entityTitleLookup ),
			$services->getLanguageFactory(),
			$entityTitleLookup,
			WikibaseClient::getKartographerEmbeddingHandler( $services ),
			$settings->getSetting( 'useKartographerMaplinkInWikitext' ),
			$services->getMainConfig()->get( 'ThumbLimits' )
		);
	},

	'WikibaseClient.DescriptionLookup' => function ( MediaWikiServices $services ): DescriptionLookup {
		return new DescriptionLookup(
			WikibaseClient::getEntityIdLookup( $services ),
			WikibaseClient::getTermBuffer( $services ),
			$services->getPageProps()
		);
	},

	'WikibaseClient.EntityChangeFactory' => function ( MediaWikiServices $services ): EntityChangeFactory {
		// TODO: take this from a setting or registry.
		$changeClasses = [
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		];

		return new EntityChangeFactory(
			WikibaseClient::getEntityDiffer( $services ),
			WikibaseClient::getEntityIdParser( $services ),
			$changeClasses,
			EntityChange::class,
			WikibaseClient::getLogger( $services )
		);
	},

	'WikibaseClient.EntityChangeLookup' => function ( MediaWikiServices $services ): EntityChangeLookup {
		return new EntityChangeLookup(
			WikibaseClient::getEntityChangeFactory( $services ),
			WikibaseClient::getEntityIdParser( $services ),
			WikibaseClient::getRepoDomainDbFactory( $services )
				->newForEntitySource( WikibaseClient::getItemAndPropertySource( $services ) )
		);
	},

	'WikibaseClient.EntityDiffer' => function ( MediaWikiServices $services ): EntityDiffer {
		$entityDiffer = new EntityDiffer();
		$entityTypeDefinitions = WikibaseClient::getEntityTypeDefinitions( $services );
		$builders = $entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_DIFFER_STRATEGY_BUILDER );
		foreach ( $builders as $builder ) {
			$entityDiffer->registerEntityDifferStrategy( $builder() );
		}
		return $entityDiffer;
	},

	'WikibaseClient.EntityIdComposer' => function ( MediaWikiServices $services ): EntityIdComposer {
		return new EntityIdComposer(
			WikibaseClient::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_ID_COMPOSER_CALLBACK )
		);
	},

	'WikibaseClient.EntityIdLookup' => function ( MediaWikiServices $services ): EntityIdLookup {
		$entityTypeDefinitions = WikibaseClient::getEntityTypeDefinitions( $services );
		return new ByTypeDispatchingEntityIdLookup(
			$entityTypeDefinitions->get( EntityTypeDefinitions::CONTENT_MODEL_ID ),
			$entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_ID_LOOKUP_CALLBACK ),
			new PagePropsEntityIdLookup(
				$services->getPageProps(),
				WikibaseClient::getEntityIdParser( $services )
			)
		);
	},

	'WikibaseClient.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		return new DispatchingEntityIdParser(
			WikibaseClient::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);
	},

	'WikibaseClient.EntityLookup' => function ( MediaWikiServices $services ): EntityLookup {
		return WikibaseClient::getStore( $services )->getEntityLookup();
	},

	'WikibaseClient.EntityNamespaceLookup' => function ( MediaWikiServices $services ): EntityNamespaceLookup {
		return array_reduce(
			WikibaseClient::getEntitySourceDefinitions( $services )->getSources(),
			function ( EntityNamespaceLookup $nsLookup, DatabaseEntitySource $source ): EntityNamespaceLookup {
				return $nsLookup->merge( new EntityNamespaceLookup(
					$source->getEntityNamespaceIds(),
					$source->getEntitySlotNames()
				) );
			},
			new EntityNamespaceLookup( [], [] )
		);
	},

	'WikibaseClient.EntityRevisionLookup' => function ( MediaWikiServices $services ): EntityRevisionLookup {
		return WikibaseClient::getStore( $services )->getEntityRevisionLookup();
	},

	'WikibaseClient.EntitySourceAndTypeDefinitions' => function ( MediaWikiServices $services ): EntitySourceAndTypeDefinitions {
		// note: when adding support for further entity source types here,
		// also adjust the default 'entitySources' setting to copy sources of those types from the repo
		return new EntitySourceAndTypeDefinitions(
			[ DatabaseEntitySource::TYPE => WikibaseClient::getEntityTypeDefinitions( $services ) ],
			WikibaseClient::getEntitySourceDefinitions( $services )->getSources()
		);
	},

	'WikibaseClient.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseClient::getSettings( $services );
		$subEntityTypesMapper = new SubEntityTypesMapper( WikibaseClient::getEntityTypeDefinitions( $services )
			->get( EntityTypeDefinitions::SUB_ENTITY_TYPES ) );

		$configParser = new EntitySourceDefinitionsConfigParser();

		return $configParser->newDefinitionsFromConfigArray( $settings->getSetting( 'entitySources' ), $subEntityTypesMapper );
	},

	'WikibaseClient.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$clientEntityTypes = require __DIR__ . '/WikibaseClient.entitytypes.php';

		$entityTypes = wfArrayPlus2d(
			$clientEntityTypes,
			$baseEntityTypes
		);

		$services->getHookContainer()->run( 'WikibaseClientEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseClient.ExternalUserNames' => function ( MediaWikiServices $services ): ?ExternalUserNames {
		$databaseName = WikibaseClient::getItemAndPropertySource( $services )->getDatabaseName();
		if ( $databaseName !== false ) {
			$siteLookup = $services->getSiteLookup();
			$repoSite = $siteLookup->getSite( $databaseName );
			if ( $repoSite === null ) {
				WikibaseClient::getLogger( $services )
					->warning(
						'WikibaseClient.ExternalUserNames service wiring: ' .
						'itemAndPropertySource databaseName {databaseName} is not known as a global site ID',
						[ 'databaseName' => $databaseName ]
					);
				return null;
			}
		} else {
			$repoSite = WikibaseClient::getSite( $services );
		}
		$interwikiPrefixes = $repoSite->getInterwikiIds();
		if ( $interwikiPrefixes === [] ) {
			WikibaseClient::getLogger( $services )
				->warning(
					'WikibaseClient.ExternalUserNames service wiring: ' .
					'repo site {siteInternalId}/{siteGlobalId} has no interwiki IDs/prefixes',
					[
						'siteInternalId' => $repoSite->getInternalId(),
						'siteGlobalId' => $repoSite->getGlobalId(),
					]
				);
			return null;
		}
		$interwikiPrefix = $interwikiPrefixes[0];
		return new ExternalUserNames( $interwikiPrefix, false );
	},

	'WikibaseClient.FallbackLabelDescriptionLookupFactory' => function (
		MediaWikiServices $services
	): FallbackLabelDescriptionLookupFactory {
		return new FallbackLabelDescriptionLookupFactory(
			WikibaseClient::getLanguageFallbackChainFactory( $services ),
			WikibaseClient::getRedirectResolvingLatestRevisionLookup( $services ),
			WikibaseClient::getTermFallbackCache( $services ),
			WikibaseClient::getTermLookup( $services ),
			WikibaseClient::getTermBuffer( $services )
		);
	},

	'WikibaseClient.HookRunner' => function ( MediaWikiServices $services ): WikibaseClientHookRunner {
		return new WikibaseClientHookRunner(
			$services->getHookContainer()
		);
	},

	'WikibaseClient.ItemAndPropertySource' => function ( MediaWikiServices $services ): EntitySource {
		$itemAndPropertySourceName = WikibaseClient::getSettings( $services )->getSetting( 'itemAndPropertySourceName' );
		$sources = WikibaseClient::getEntitySourceDefinitions( $services )->getSources();
		foreach ( $sources as $source ) {
			if ( $source->getSourceName() === $itemAndPropertySourceName ) {
				return $source;
			}
		}

		throw new LogicException( 'No source configured: ' . $itemAndPropertySourceName );
	},

	'WikibaseClient.ItemSource' => function ( MediaWikiServices $services ): EntitySource {
		$itemSource = WikibaseClient::getEntitySourceDefinitions( $services )
			->getDatabaseSourceForEntityType( Item::ENTITY_TYPE );

		if ( $itemSource === null ) {
			throw new LogicException( 'No source providing Items configured!' );
		}

		return $itemSource;
	},

	'WikibaseClient.KartographerEmbeddingHandler' => function (
		MediaWikiServices $services
	): ?CachingKartographerEmbeddingHandler {
		$settings = WikibaseClient::getSettings( $services );

		if (
			$settings->getSetting( 'useKartographerGlobeCoordinateFormatter' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' ) // TODO T257586
		) {
			return new CachingKartographerEmbeddingHandler(
				$services->getParserFactory()->create()
			);
		} else {
			return null;
		}
	},

	'WikibaseClient.LangLinkHandlerFactory' => function ( MediaWikiServices $services ): LangLinkHandlerFactory {
		return new LangLinkHandlerFactory(
			WikibaseClient::getLanguageLinkBadgeDisplay( $services ),
			WikibaseClient::getNamespaceChecker( $services ),
			WikibaseClient::getStore( $services )->getSiteLinkLookup(),
			WikibaseClient::getEntityLookup( $services ),
			$services->getSiteLookup(),
			$services->getHookContainer(),
			WikibaseClient::getLogger( $services ),
			WikibaseClient::getSettings( $services )->getSetting( 'siteGlobalID' ),
			WikibaseClient::getLangLinkSiteGroups( $services )
		);
	},

	'WikibaseClient.LangLinkSiteGroup' => function ( MediaWikiServices $services ): string {
		$group = WikibaseClient::getSettings( $services )
			->getSetting( 'languageLinkSiteGroup' );

		if ( $group === null ) {
			$group = WikibaseClient::getSiteGroup( $services );
		}

		return $group;
	},

	'WikibaseClient.LangLinkSiteGroups' => function ( MediaWikiServices $services ): array {
		$groups = WikibaseClient::getSettings( $services )->getSetting( 'languageLinkAllowedSiteGroups' );
		if ( $groups === null ) {
			$groups = [ WikibaseClient::getLangLinkSiteGroup( $services ) ];
		}

		return $groups;
	},

	'WikibaseClient.LanguageFallbackChainFactory' => function ( MediaWikiServices $services ): LanguageFallbackChainFactory {
		return new LanguageFallbackChainFactory(
			WikibaseClient::getTermsLanguages( $services ),
			$services->getLanguageFactory(),
			$services->getLanguageConverterFactory(),
			$services->getLanguageFallback()
		);
	},

	'WikibaseClient.LanguageLinkBadgeDisplay' => function ( MediaWikiServices $services ): LanguageLinkBadgeDisplay {
		return new LanguageLinkBadgeDisplay(
			WikibaseClient::getSidebarLinkBadgeDisplay( $services )
		);
	},

	'WikibaseClient.Logger' => function ( MediaWikiServices $services ): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikibase' );
	},

	'WikibaseClient.MessageInLanguageProvider' => function ( MediaWikiServices $services ): MessageInLanguageProvider {
		return new MediaWikiMessageInLanguageProvider();
	},

	'WikibaseClient.NamespaceChecker' => function ( MediaWikiServices $services ): NamespaceChecker {
		$settings = WikibaseClient::getSettings( $services );
		return new NamespaceChecker(
			$settings->getSetting( 'excludeNamespaces' ),
			$settings->getSetting( 'namespaces' ),
			$services->getNamespaceInfo()
		);
	},

	'WikibaseClient.OtherProjectsSidebarGeneratorFactory' => function (
		MediaWikiServices $services
	): OtherProjectsSidebarGeneratorFactory {
		return new OtherProjectsSidebarGeneratorFactory(
			WikibaseClient::getSettings( $services ),
			WikibaseClient::getStore( $services )->getSiteLinkLookup(),
			$services->getSiteLookup(),
			WikibaseClient::getEntityLookup( $services ),
			WikibaseClient::getSidebarLinkBadgeDisplay( $services ),
			$services->getHookContainer(),
			WikibaseClient::getLogger( $services )
		);
	},

	'WikibaseClient.OtherProjectsSitesProvider' => function ( MediaWikiServices $services ): OtherProjectsSitesProvider {
		$settings = WikibaseClient::getSettings( $services );

		return new CachingOtherProjectsSitesProvider(
			new OtherProjectsSitesGenerator(
				$services->getSiteLookup(),
				$settings->getSetting( 'siteGlobalID' ),
				$settings->getSetting( 'specialSiteLinkGroups' )
			),
			// TODO: Make configurable? Should be similar, maybe identical to sharedCacheType and
			// sharedCacheDuration, but can not reuse these because this here is not shared.
			ObjectCache::getLocalClusterInstance(),
			60 * 60
		);
	},

	'WikibaseClient.ParserOutputDataUpdater' => function ( MediaWikiServices $services ): ClientParserOutputDataUpdater {
		$settings = WikibaseClient::getSettings( $services );

		return new ClientParserOutputDataUpdater(
			WikibaseClient::getOtherProjectsSidebarGeneratorFactory( $services ),
			WikibaseClient::getStore( $services )->getSiteLinkLookup(),
			WikibaseClient::getEntityLookup( $services ),
			WikibaseClient::getUsageAccumulatorFactory( $services ),
			$settings->getSetting( 'siteGlobalID' ),
			WikibaseClient::getLogger( $services )
		);
	},

	'WikibaseClient.PrefetchingTermLookup' => function ( MediaWikiServices $services ): PrefetchingTermLookup {
		return new SourceAndTypeDispatchingPrefetchingTermLookup(
			new ServiceBySourceAndTypeDispatcher(
				PrefetchingTermLookup::class,
				WikibaseClient::getEntitySourceAndTypeDefinitions( $services )
					->getServiceBySourceAndType( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK )
			),
			new EntitySourceLookup(
				WikibaseClient::getEntitySourceDefinitions( $services ),
				new SubEntityTypesMapper( WikibaseClient::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::SUB_ENTITY_TYPES ) )
			)
		);
	},

	'WikibaseClient.PropertyDataTypeLookup' => function ( MediaWikiServices $services ): PropertyDataTypeLookup {
		$infoLookup = WikibaseClient::getStore( $services )->getPropertyInfoLookup();
		$entityLookup = WikibaseClient::getEntityLookup( $services );
		$retrievingLookup = new EntityRetrievingDataTypeLookup( $entityLookup );
		return new PropertyInfoDataTypeLookup(
			$infoLookup,
			WikibaseClient::getLogger( $services ),
			$retrievingLookup
		);
	},

	'WikibaseClient.PropertyLabelResolver' => function ( MediaWikiServices $services ): PropertyLabelResolver {
		// Required services
		$languageCode = $services->getContentLanguage()->getCode();

		$settings = WikibaseClient::getSettings( $services );
		$cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );

		// Cache key needs to be language specific
		$cacheKey = $cacheKeyPrefix . ':TermPropertyLabelResolver' . '/' . $languageCode;

		$repoDb = WikibaseClient::getRepoDomainDbFactory( $services )
			->newForEntitySource( WikibaseClient::getPropertySource( $services ) );
		$wanObjectCache = $services->getMainWANObjectCache();

		$typeIdsStore = new DatabaseTypeIdsStore(
			$repoDb,
			$wanObjectCache
		);

		$databaseTermIdsResolver = new DatabaseTermInLangIdsResolver(
			$typeIdsStore,
			$typeIdsStore,
			$repoDb
		);

		return new CachedDatabasePropertyLabelResolver(
			$languageCode,
			$databaseTermIdsResolver,
			ObjectCache::getInstance( $cacheType ),
			$cacheDuration,
			$cacheKey
		);
	},

	'WikibaseClient.PropertyOrderProvider' => function ( MediaWikiServices $services ): CachingPropertyOrderProvider {
		$title = $services->getTitleFactory()->newFromTextThrow( 'MediaWiki:Wikibase-SortedProperties' );
		$innerProvider = new WikiPagePropertyOrderProvider( $services->getWikiPageFactory(), $title );

		$url = WikibaseClient::getSettings( $services )->getSetting( 'propertyOrderUrl' );

		if ( $url !== null ) {
			$innerProvider = new FallbackPropertyOrderProvider(
				$innerProvider,
				new HttpUrlPropertyOrderProvider(
					$url,
					$services->getHttpRequestFactory(),
					WikibaseClient::getLogger( $services )
				)
			);
		}

		return new CachingPropertyOrderProvider(
			$innerProvider,
			ObjectCache::getLocalClusterInstance()
		);
	},

	'WikibaseClient.PropertyParserFunctionRunner' => function ( MediaWikiServices $services ): Runner {
		$settings = WikibaseClient::getSettings( $services );
		return new Runner(
			WikibaseClient::getStatementGroupRendererFactory( $services ),
			WikibaseClient::getStore( $services )->getSiteLinkLookup(),
			WikibaseClient::getEntityIdParser( $services ),
			WikibaseClient::getRestrictedEntityLookup( $services ),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'allowArbitraryDataAccess' )
		);
	},

	'WikibaseClient.PropertySource' => function ( MediaWikiServices $services ): EntitySource {
		$propertySource = WikibaseClient::getEntitySourceDefinitions( $services )
			->getDatabaseSourceForEntityType( Property::ENTITY_TYPE );

		if ( $propertySource === null ) {
			throw new LogicException( 'No source providing Properties configured!' );
		}

		return $propertySource;
	},

	'WikibaseClient.RecentChangeFactory' => function ( MediaWikiServices $services ): RecentChangeFactory {
		$contentLanguage = $services->getContentLanguage();
		$siteLookup = $services->getSiteLookup();

		return new RecentChangeFactory(
			$contentLanguage,
			new SiteLinkCommentCreator(
				$contentLanguage,
				$siteLookup,
				WikibaseClient::getSettings( $services )->getSetting( 'siteGlobalID' )
			),
			WikibaseClient::getEntitySourceDefinitions( $services ),
			WikibaseClient::getClientDomainDbFactory( $services )->newLocalDb(),
			$services->getCentralIdLookupFactory()->getNonLocalLookup(),
			WikibaseClient::getExternalUserNames( $services )
		);
	},

	'WikibaseClient.RedirectResolvingLatestRevisionLookup' => function (
		MediaWikiServices $services
	): RedirectResolvingLatestRevisionLookup {
		return new RedirectResolvingLatestRevisionLookup(
			WikibaseClient::getEntityRevisionLookup( $services )
		);
	},

	'WikibaseClient.ReferenceFormatterFactory' => function ( MediaWikiServices $services ): ReferenceFormatterFactory {
		$logger = WikibaseClient::getLogger( $services );
		return new ReferenceFormatterFactory(
			WikibaseClient::getDataAccessSnakFormatterFactory( $services ),
			WellKnownReferenceProperties::newFromArray(
				WikibaseClient::getSettings( $services )
					->getSetting( 'wellKnownReferencePropertyIds' ),
				$logger
			),
			$logger
		);
	},

	'WikibaseClient.RepoDomainDbFactory' => function ( MediaWikiServices $services ): RepoDomainDbFactory {
		$lbFactory = $services->getDBLoadBalancerFactory();

		return new RepoDomainDbFactory(
			$lbFactory,
			WikibaseClient::getItemAndPropertySource( $services )->getDatabaseName() ?: $lbFactory->getLocalDomainID(),
			[ DomainDb::LOAD_GROUP_FROM_CLIENT ]
		);
	},

	'WikibaseClient.RepoItemUriParser' => function ( MediaWikiServices $services ): EntityIdParser {
		$itemConceptUriBase = WikibaseClient::getItemSource( $services )
			->getConceptBaseUri();

		return new SuffixEntityIdParser(
			$itemConceptUriBase,
			new ItemIdParser()
		);
	},

	'WikibaseClient.RepoLinker' => function ( MediaWikiServices $services ): RepoLinker {
		$settings = WikibaseClient::getSettings( $services );

		return new RepoLinker(
			WikibaseClient::getEntitySourceDefinitions( $services ),
			$settings->getSetting( 'repoUrl' ),
			$settings->getSetting( 'repoArticlePath' ),
			$settings->getSetting( 'repoScriptPath' )
		);
	},

	'WikibaseClient.RestrictedEntityLookup' => function ( MediaWikiServices $services ): RestrictedEntityLookup {
		$settings = WikibaseClient::getSettings( $services );
		$disabledEntityTypesEntityLookup = new DisabledEntityTypesEntityLookup(
			WikibaseClient::getEntityLookup( $services ),
			$settings->getSetting( 'disabledAccessEntityTypes' )
		);
		return new RestrictedEntityLookup(
			$disabledEntityTypesEntityLookup,
			$settings->getSetting( 'entityAccessLimit' )
		);
	},

	'WikibaseClient.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getClientSettings();
	},

	'WikibaseClient.SidebarLinkBadgeDisplay' => function ( MediaWikiServices $services ): SidebarLinkBadgeDisplay {
		$badgeClassNames = WikibaseClient::getSettings( $services )->getSetting( 'badgeClassNames' );
		$labelDescriptionLookupFactory = WikibaseClient::getFallbackLabelDescriptionLookupFactory( $services );
		$lang = WikibaseClient::getUserLanguage( $services );

		return new SidebarLinkBadgeDisplay(
			$labelDescriptionLookupFactory->newLabelDescriptionLookup( $lang ),
			is_array( $badgeClassNames ) ? $badgeClassNames : [],
			$lang
		);
	},

	'WikibaseClient.Site' => function ( MediaWikiServices $services ): Site {
		$settings = WikibaseClient::getSettings( $services );
		$globalId = $settings->getSetting( 'siteGlobalID' );
		$localId = $settings->getSetting( 'siteLocalID' );

		$site = $services->getSiteLookup()->getSite( $globalId );

		$logger = WikibaseClient::getLogger( $services );

		if ( !$site ) {
			$logger->debug(
				'WikibaseClient.ServiceWiring.php::WikibaseClient.Site: ' .
				'Unable to resolve site ID {globalId}!',
				[ 'globalId' => $globalId ]
			);

			$site = new MediaWikiSite();
			$site->setGlobalId( $globalId );
			$site->addLocalId( Site::ID_INTERWIKI, $localId );
			$site->addLocalId( Site::ID_EQUIVALENT, $localId );
		}

		if ( !in_array( $localId, array_merge( [], ...array_values( $site->getLocalIds() ) ) ) ) {
			$logger->debug(
				'WikibaseClient.ServiceWiring.php::WikibaseClient.Site: ' .
				'The configured local id {localId} does not match any local IDs of site {globalId}: {localIds}',
				[
					'localId' => $localId,
					'globalId' => $globalId,
					'localIds' => json_encode( $site->getLocalIds() ),
				]
			);
		}

		return $site;
	},

	'WikibaseClient.SiteGroup' => function ( MediaWikiServices $services ): string {
		$settings = WikibaseClient::getSettings( $services );
		$siteGroup = $settings->getSetting( 'siteGroup' );

		if ( !$siteGroup ) {
			$siteId = $settings->getSetting( 'siteGlobalID' );

			$site = $services->getSiteLookup()->getSite( $siteId );

			if ( !$site ) {
				// TODO we should log some warning here,
				// but currently that breaks CI (T153729, T153597)
				return Site::GROUP_NONE;
			}

			$siteGroup = $site->getGroup();
		}

		return $siteGroup;
	},

	'WikibaseClient.SnakFormatterFactory' => function ( MediaWikiServices $services ): OutputFormatSnakFormatterFactory {
		return new OutputFormatSnakFormatterFactory(
			WikibaseClient::getDataTypeDefinitions( $services )
				->getSnakFormatterFactoryCallbacks(),
			WikibaseClient::getValueFormatterFactory( $services ),
			WikibaseClient::getPropertyDataTypeLookup( $services ),
			WikibaseClient::getDataTypeFactory( $services ),
			WikibaseClient::getMessageInLanguageProvider( $services )
		);
	},

	'WikibaseClient.StatementGroupRendererFactory' => function ( MediaWikiServices $services ): StatementGroupRendererFactory {
		return new StatementGroupRendererFactory(
			WikibaseClient::getPropertyLabelResolver( $services ),
			new SnaksFinder(),
			WikibaseClient::getRestrictedEntityLookup( $services ),
			WikibaseClient::getDataAccessSnakFormatterFactory( $services ),
			WikibaseClient::getUsageAccumulatorFactory( $services ),
			$services->getLanguageConverterFactory(),
			$services->getLanguageFactory(),
			WikibaseClient::getSettings( $services )
				->getSetting( 'allowDataAccessInUserLanguage' )
		);
	},

	'WikibaseClient.Store' => function ( MediaWikiServices $services ): ClientStore {
		return new DirectSqlStore(
			WikibaseClient::getEntityIdParser( $services ),
			WikibaseClient::getEntityIdLookup( $services ),
			WikibaseClient::getWikibaseServices( $services ),
			WikibaseClient::getSettings( $services ),
			WikibaseClient::getTermBuffer( $services ),
			WikibaseClient::getRepoDomainDbFactory( $services )
				->newForEntitySource( WikibaseClient::getItemAndPropertySource( $services ) ),
			WikibaseClient::getClientDomainDbFactory( $services )->newLocalDb()
		);
	},

	'WikibaseClient.StringNormalizer' => function ( MediaWikiServices $services ): StringNormalizer {
		return new StringNormalizer();
	},

	'WikibaseClient.TermBuffer' => function ( MediaWikiServices $services ): TermBuffer {
		return WikibaseClient::getPrefetchingTermLookup( $services );
	},

	'WikibaseClient.TermFallbackCache' => function ( MediaWikiServices $services ): TermFallbackCacheFacade {
		return new TermFallbackCacheFacade(
			WikibaseClient::getTermFallbackCacheFactory( $services )->getTermFallbackCache(),
			WikibaseClient::getSettings( $services )->getSetting( 'sharedCacheDuration' )
		);
	},

	'WikibaseClient.TermFallbackCacheFactory' => function ( MediaWikiServices $services ): TermFallbackCacheFactory {
		$settings = WikibaseClient::getSettings( $services );
		return new TermFallbackCacheFactory(
			$settings->getSetting( 'sharedCacheType' ),
			WikibaseClient::getLogger( $services ),
			$services->getStatsdDataFactory(),
			hash( 'sha256', $services->getMainConfig()->get( 'SecretKey' ) ),
			new TermFallbackCacheServiceFactory(),
			$settings->getSetting( 'termFallbackCacheVersion' )
		);
	},

	'WikibaseClient.TermInLangIdsResolverFactory' => function (
		MediaWikiServices $services
	): TermInLangIdsResolverFactory {
		return new TermInLangIdsResolverFactory(
			WikibaseClient::getRepoDomainDbFactory( $services ),
			WikibaseClient::getLogger( $services ),
			$services->getMainWANObjectCache()
		);
	},

	'WikibaseClient.TermLookup' => function ( MediaWikiServices $services ): TermLookup {
		return WikibaseClient::getPrefetchingTermLookup( $services );
	},

	'WikibaseClient.TermsLanguages' => function ( MediaWikiServices $services ): ContentLanguages {
		return WikibaseClient::getWikibaseContentLanguages( $services )
			->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
	},

	'WikibaseClient.UsageAccumulatorFactory' => function ( MediaWikiServices $services ): UsageAccumulatorFactory {
		$usageModifierLimits = WikibaseClient::getSettings( $services )->getSetting(
			'entityUsageModifierLimits'
		);
		return new UsageAccumulatorFactory(
			new EntityUsageFactory( WikibaseClient::getEntityIdParser( $services ) ),
			new UsageDeduplicator( $usageModifierLimits ),
			new RevisionBasedEntityRedirectTargetLookup(
				WikibaseClient::getEntityRevisionLookup( $services )
			)
		);
	},

	'WikibaseClient.UserLanguage' => function ( MediaWikiServices $services ): Language {
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

	'WikibaseClient.ValueFormatterFactory' => function ( MediaWikiServices $services ): OutputFormatValueFormatterFactory {
		return new OutputFormatValueFormatterFactory(
			WikibaseClient::getDataTypeDefinitions( $services )
				->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			$services->getContentLanguage(),
			WikibaseClient::getLanguageFallbackChainFactory( $services )
		);
	},

	'WikibaseClient.WikibaseContentLanguages' => function ( MediaWikiServices $services ): WikibaseContentLanguages {
		return WikibaseContentLanguages::getDefaultInstance(
			$services->getHookContainer(),
			$services->getLanguageNameUtils()
		);
	},

	'WikibaseClient.WikibaseServices' => function ( MediaWikiServices $services ): WikibaseServices {
		$entitySourceDefinitions = WikibaseClient::getEntitySourceDefinitions( $services );
		$singleEntitySourceServicesFactory = new SingleEntitySourceServicesFactory(
			WikibaseClient::getEntityIdParser( $services ),
			WikibaseClient::getEntityIdComposer( $services ),
			WikibaseClient::getDataValueDeserializer( $services ),
			$services->getNameTableStoreFactory(),
			WikibaseClient::getDataAccessSettings( $services ),
			WikibaseClient::getLanguageFallbackChainFactory( $services ),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			WikibaseClient::getEntityTypeDefinitions( $services ),
			WikibaseClient::getRepoDomainDbFactory( $services )
		);

		$singleSourceServices = [];
		foreach ( $entitySourceDefinitions->getSources() as $source ) {
			$singleSourceServices[$source->getSourceName()] = $singleEntitySourceServicesFactory
				->getServicesForSource( $source );
		}

		return new MultipleEntitySourceServices( $entitySourceDefinitions, $singleSourceServices );
	},

];
