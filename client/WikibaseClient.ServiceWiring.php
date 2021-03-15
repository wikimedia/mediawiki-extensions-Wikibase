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
use Wikibase\Client\EntitySourceDefinitionsLegacyClientSettingsParser;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\OtherProjectsSitesGenerator;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\ByTypeDispatchingEntityIdLookup;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\WikibaseSettings;

/** @phpcs-require-sorted-array */
return [

	'WikibaseClient.BaseDataModelDeserializerFactory' => function ( MediaWikiServices $services ): DeserializerFactory {
		return new DeserializerFactory(
			WikibaseClient::getDataValueDeserializer( $services ),
			WikibaseClient::getEntityIdParser( $services )
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

	'WikibaseClient.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$clientDataTypes = require __DIR__ . '/WikibaseClient.datatypes.php';

		$dataTypes = array_merge_recursive( $baseDataTypes, $clientDataTypes );

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
				$services->getDBLoadBalancer(),
				WikibaseClient::getEntityIdParser( $services )
			)
		);
	},

	'WikibaseClient.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		return new DispatchingEntityIdParser(
			WikibaseClient::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);
	},

	'WikibaseClient.EntityNamespaceLookup' => function ( MediaWikiServices $services ): EntityNamespaceLookup {
		return array_reduce(
			WikibaseClient::getEntitySourceDefinitions( $services )->getSources(),
			function ( EntityNamespaceLookup $nsLookup, EntitySource $source ): EntityNamespaceLookup {
				return $nsLookup->merge( new EntityNamespaceLookup(
					$source->getEntityNamespaceIds(),
					$source->getEntitySlotNames()
				) );
			},
			new EntityNamespaceLookup( [], [] )
		);
	},

	// TODO: current settings (especially (foreign) repositories blob) might be quite confusing
	// Having a "entitySources" or so setting might be better, and would also allow unifying
	// the way these are configured in Repo and in Client parts
	'WikibaseClient.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseClient::getSettings( $services );
		$entityTypeDefinitions = WikibaseClient::getEntityTypeDefinitions( $services );

		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray( $settings->getSetting( 'entitySources' ), $entityTypeDefinitions );
		}

		$parser = new EntitySourceDefinitionsLegacyClientSettingsParser();
		return $parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions );
	},

	'WikibaseClient.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$entityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';

		$services->getHookContainer()->run( 'WikibaseClientEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseClient.LanguageFallbackChainFactory' => function ( MediaWikiServices $services ): LanguageFallbackChainFactory {
		return new LanguageFallbackChainFactory(
			$services->getLanguageFactory(),
			$services->getLanguageConverterFactory(),
			$services->getLanguageFallback()
		);
	},

	'WikibaseClient.Logger' => function ( MediaWikiServices $services ): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikibase' );
	},

	'WikibaseClient.NamespaceChecker' => function ( MediaWikiServices $services ): NamespaceChecker {
		$settings = WikibaseClient::getSettings( $services );
		return new NamespaceChecker(
			$settings->getSetting( 'excludeNamespaces' ),
			$settings->getSetting( 'namespaces' ),
			$services->getNamespaceInfo()
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

	'WikibaseClient.PropertyOrderProvider' => function ( MediaWikiServices $services ): CachingPropertyOrderProvider {
		$title = $services->getTitleFactory()->newFromTextThrow( 'MediaWiki:Wikibase-SortedProperties' );
		$innerProvider = new WikiPagePropertyOrderProvider( $title );

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

	'WikibaseClient.RepoLinker' => function ( MediaWikiServices $services ): RepoLinker {
		$settings = WikibaseClient::getSettings( $services );

		return new RepoLinker(
			WikibaseClient::getEntitySourceDefinitions( $services ),
			$settings->getSetting( 'repoUrl' ),
			$settings->getSetting( 'repoArticlePath' ),
			$settings->getSetting( 'repoScriptPath' )
		);
	},

	'WikibaseClient.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getClientSettings();
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
					'localIds' => json_encode( $site->getLocalIds() )
				]
			);
		}

		return $site;
	},

	'WikibaseClient.StringNormalizer' => function ( MediaWikiServices $services ): StringNormalizer {
		return new StringNormalizer();
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

	'WikibaseClient.ValueFormatterFactory' => function ( MediaWikiServices $services ): OutputFormatValueFormatterFactory {
		return new OutputFormatValueFormatterFactory(
			WikibaseClient::getDataTypeDefinitions( $services )
				->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			$services->getContentLanguage(),
			WikibaseClient::getLanguageFallbackChainFactory( $services )
		);
	},

	'WikibaseClient.WikibaseServices' => function ( MediaWikiServices $services ): WikibaseServices {
		$nameTableStoreFactory = $services->getNameTableStoreFactory();
		$entityTypeDefinitions = WikibaseClient::getEntityTypeDefinitions( $services );
		$genericServices = new GenericServices( $entityTypeDefinitions );

		$entitySourceDefinitions = WikibaseClient::getEntitySourceDefinitions( $services );
		$singleSourceServices = [];
		foreach ( $entitySourceDefinitions->getSources() as $source ) {
			// TODO: extract
			$singleSourceServices[$source->getSourceName()] = new SingleEntitySourceServices(
				$genericServices,
				WikibaseClient::getEntityIdParser( $services ),
				WikibaseClient::getEntityIdComposer( $services ),
				WikibaseClient::getDataValueDeserializer( $services ),
				$nameTableStoreFactory->getSlotRoles( $source->getDatabaseName() ),
				WikibaseClient::getDataAccessSettings( $services ),
				$source,
				WikibaseClient::getLanguageFallbackChainFactory( $services ),
				new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
				$entityTypeDefinitions->get( EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK ),
				$entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_METADATA_ACCESSOR_CALLBACK ),
				$entityTypeDefinitions->get( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK ),
				$entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK )
			);
		}

		return new MultipleEntitySourceServices( $entitySourceDefinitions, $genericServices, $singleSourceServices );
	},

];
