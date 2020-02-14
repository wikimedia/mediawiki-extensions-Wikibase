<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\ByTypeDispatchingEntityInfoBuilder;
use Wikibase\DataAccess\ByTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\ByIdDispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoreDelegatingMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
use Wikibase\Store\BufferingTermIndexTermLookup;
use Wikibase\Lib\Store\TermIndex;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */

return [

	'EntityInfoBuilder' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		global $wgSecretKey;

		$entityNamespaceLookup = $genericServices->getEntityNamespaceLookup();
		$repositoryName = '';
		$databaseName = $services->getDatabaseName();

		$cacheSecret = hash( 'sha256', $wgSecretKey );

		$cache = new SimpleCacheWithBagOStuff(
			MediaWikiServices::getInstance()->getLocalServerObjectCache(),
			'wikibase.sqlEntityInfoBuilder.',
			$cacheSecret
		);
		$cache = new StatsdRecordingSimpleCache(
			$cache,
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			[
				'miss' => 'wikibase.sqlEntityInfoBuilder.miss',
				'hit' => 'wikibase.sqlEntityInfoBuilder.hit'
			]
		);

		$mediaWikiServices = MediaWikiServices::getInstance();
		$logger = LoggerFactory::getInstance( 'Wikibase' );
		$loadBalancerFactory = $mediaWikiServices->getDBLoadBalancerFactory();
		$loadBalancer = $loadBalancerFactory->getMainLB( $databaseName );
		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$mediaWikiServices->getMainWANObjectCache(),
			$databaseName,
			$logger
		);
		$termIdsResolver = new DatabaseTermInLangIdsResolver(
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$loadBalancer,
			$databaseName,
			$logger
		);

		$oldEntityInfoBuilder = new SqlEntityInfoBuilder(
			$services->getEntityIdParser(),
			$entityNamespaceLookup,
			$logger,
			new UnusableEntitySource(),
			$cache
		);

		$newEntityInfoBuilder = new DatabaseEntityInfoBuilder(
			$services->getEntityIdParser(),
			$services->getEntityIdComposer(),
			$entityNamespaceLookup,
			$logger,
			new UnusableEntitySource(),
			$settings,
			$cache,
			$loadBalancer,
			$termIdsResolver,
			$repositoryName,
			$databaseName
		);

		$typeDispatchingMapping = [];

		// Properties
		if ( $settings->useNormalizedPropertyTerms() === true ) {
			$typeDispatchingMapping[Property::ENTITY_TYPE] = $newEntityInfoBuilder;
		} else {
			$typeDispatchingMapping[Property::ENTITY_TYPE] = $oldEntityInfoBuilder;
		}

		// Items
		$itemEntityInfoBuilderMapping = [];
		foreach ( $settings->getItemTermsMigrationStages() as $maxId => $stage ) {
			if ( $stage >= MIGRATION_WRITE_NEW ) {
				$itemEntityInfoBuilderMapping[$maxId] = $newEntityInfoBuilder;
			} else {
				$itemEntityInfoBuilderMapping[$maxId] = $oldEntityInfoBuilder;
			}
		}
		$typeDispatchingMapping[Item::ENTITY_TYPE] = new ByIdDispatchingEntityInfoBuilder( $itemEntityInfoBuilderMapping );

		return new ByTypeDispatchingEntityInfoBuilder( $typeDispatchingMapping );
	},

	'EntityPrefetcher' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		$prefetcher = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		Assert::postcondition(
			$prefetcher instanceof EntityPrefetcher,
			'The WikiPageEntityMetaDataAccessor service is expected to implement EntityPrefetcher interface.'
		);

		return $prefetcher;
	},

	'EntityRevisionLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$serializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
		} elseif ( $services->getRepositoryName() !== '' ) {
			$serializer = new ForbiddenSerializer( 'Serialization of foreign entities is not supported!' );
		} else {
			$serializer = $genericServices->getStorageEntitySerializer();
		}

		$codec = new EntityContentDataCodec(
			$services->getEntityIdParser(),
			$serializer,
			$services->getEntityDeserializer(),
			$settings->maxSerializedEntitySizeInBytes()
		);

		/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
		$metaDataAccessor = $services->getService( 'WikiPageEntityMetaDataAccessor' );

		$revisionStoreFactory = \MediaWiki\MediaWikiServices::getInstance()->getRevisionStoreFactory();
		$blobStoreFactory = \MediaWiki\MediaWikiServices::getInstance()->getBlobStoreFactory();

		return new WikiPageEntityRevisionLookup(
			$metaDataAccessor,
			new WikiPageEntityDataLoader( $codec, $blobStoreFactory->newBlobStore( $services->getDatabaseName() ) ),
			$revisionStoreFactory->getRevisionStore( $services->getDatabaseName() ),
			$services->getDatabaseName()
		);
	},

	'PrefetchingTermLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		global $wgSecretKey;

		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );

		$termIndexBackedTermLookup = new BufferingTermIndexTermLookup(
			$termIndex, // TODO: customize buffer sizes
			1000
		);

		$mediaWikiServices = MediaWikiServices::getInstance();
		$logger = LoggerFactory::getInstance( 'Wikibase' );

		$repoDbDomain = $services->getDatabaseName();
		$loadBalancer = $mediaWikiServices->getDBLoadBalancerFactory()->getMainLB( $repoDbDomain );
		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$mediaWikiServices->getMainWANObjectCache(),
			$repoDbDomain,
			$logger
		);

		$termIdsResolver = new DatabaseTermInLangIdsResolver(
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$loadBalancer,
			$repoDbDomain,
			$logger
		);

		$cacheSecret = hash( 'sha256', $wgSecretKey );

		$cache = new SimpleCacheWithBagOStuff(
			MediaWikiServices::getInstance()->getLocalServerObjectCache(),
			'wikibase.prefetchingItemTermLookup.',
			$cacheSecret
		);
		$cache = new StatsdRecordingSimpleCache(
			$cache,
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			[
				'miss' => 'wikibase.prefetchingItemTermLookupCache.miss',
				'hit' => 'wikibase.prefetchingItemTermLookupCache.hit'
			]
		);
		$redirectResolvingRevisionLookup = new RedirectResolvingLatestRevisionLookup(
			$services->getService( 'EntityRevisionLookup' )
		);

		$itemLookup = new TermStoresDelegatingPrefetchingItemTermLookup(
			$settings,
			new PrefetchingItemTermLookup( $loadBalancer, $termIdsResolver, $repoDbDomain ),
			$termIndexBackedTermLookup
		);
		$lookups = [];
		$contentLanguages = WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
		$lookups['item'] = new CachingPrefetchingTermLookup(
			$cache,
			new UncachedTermsPrefetcher(
				$itemLookup,
				$redirectResolvingRevisionLookup
			),
			$redirectResolvingRevisionLookup,
			$contentLanguages
		);

		if ( $settings->useNormalizedPropertyTerms() ) {
			$cache = new SimpleCacheWithBagOStuff(
				MediaWikiServices::getInstance()->getLocalServerObjectCache(),
				'wikibase.prefetchingPropertyTermLookup.',
				$cacheSecret
			);
			$cache = new StatsdRecordingSimpleCache(
				$cache,
				MediaWikiServices::getInstance()->getStatsdDataFactory(),
				[
					'miss' => 'wikibase.prefetchingPropertyTermLookupCache.miss',
					'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit'
				]
			);
			$lookups['property'] = new CachingPrefetchingTermLookup(
				$cache,
				new UncachedTermsPrefetcher(
					new PrefetchingPropertyTermLookup( $loadBalancer, $termIdsResolver, $repoDbDomain ),
					$redirectResolvingRevisionLookup,
					60 // 1 minute ttl
				),
				$redirectResolvingRevisionLookup,
				$contentLanguages
			);
		} else {
			$lookups['property'] = $termIndexBackedTermLookup;
		}

		return new ByTypeDispatchingPrefetchingTermLookup( $lookups, new NullPrefetchingTermLookup() );
	},

	'PropertyInfoLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		return new PropertyInfoTable(
			$services->getEntityIdComposer(),
			new UnusableEntitySource()
		);
	},

	'TermBuffer' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		return $services->getService( 'PrefetchingTermLookup' );
	},

	'TermIndex' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		$index = new TermSqlIndex(
			$genericServices->getStringNormalizer(),
			$services->getEntityIdComposer(),
			$services->getEntityIdParser(),
			new UnusableEntitySource(),
			$settings,
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
		$index->setUseSearchFields( $settings->useSearchFields() );
		$index->setForceWriteSearchFields( $settings->forceWriteSearchFields() );
		return $index;
	},

	'MatchingTermsLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		$mediaWikiServices = MediaWikiServices::getInstance();
		$logger = LoggerFactory::getInstance( 'Wikibase' );
		$repoDbDomain = $services->getDatabaseName();
		$loadBalancer = $mediaWikiServices->getDBLoadBalancerFactory()->getMainLB( $repoDbDomain );
		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$mediaWikiServices->getMainWANObjectCache(),
			$repoDbDomain,
			$logger
		);
		$matchingTermsLookup = new DatabaseMatchingTermsLookup(
			$loadBalancer,
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$services->getEntityIdComposer(),
			$logger
		);
		return $matchingTermsLookup;
	},

	'TermSearchInteractorFactory' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		/** @var PrefetchingTermLookup $prefetchingTermLookup */
		$prefetchingTermLookup = $services->getService( 'PrefetchingTermLookup' );
		$delegatingMatchingTermsLookup = new TermStoreDelegatingMatchingTermsLookup(
			$services->getService( 'TermIndex' ),
			$services->getService( 'MatchingTermsLookup' ),
			$settings->itemSearchMigrationStage(),
			$settings->propertySearchMigrationStage()
		);

		return new MatchingTermsSearchInteractorFactory(
			$delegatingMatchingTermsLookup,
			$genericServices->getLanguageFallbackChainFactory(),
			$prefetchingTermLookup
		);
	},

	'WikiPageEntityMetaDataAccessor' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		$entityNamespaceLookup = $genericServices->getEntityNamespaceLookup();
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new TypeDispatchingWikiPageEntityMetaDataAccessor(
				$services->getEntityMetaDataAccessorCallbacks(),
				new WikiPageEntityMetaDataLookup(
					$entityNamespaceLookup,
					new EntityIdLocalPartPageTableEntityQuery(
						$entityNamespaceLookup,
						$services->getSlotRoleStore()
					),
					new UnusableEntitySource()
				),
				$services->getDatabaseName(),
				$services->getRepositoryName()
			),
			LoggerFactory::getInstance( 'Wikibase' )
		);
	},

];
