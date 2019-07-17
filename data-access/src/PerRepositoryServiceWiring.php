<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\ByTypeDispatchingPrefetchingTermLookup;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndex;
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
		return new SqlEntityInfoBuilder(
			$services->getEntityIdParser(),
			$services->getEntityIdComposer(),
			$genericServices->getEntityNamespaceLookup(),
			LoggerFactory::getInstance( 'Wikibase' ),
			new UnusableEntitySource(),
			$settings,
			$services->getDatabaseName(),
			$services->getRepositoryName()
		);
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
			$codec,
			$metaDataAccessor,
			$revisionStoreFactory->getRevisionStore( $services->getDatabaseName() ),
			$blobStoreFactory->newBlobStore( $services->getDatabaseName() ),
			$services->getDatabaseName()
		);
	},

	'PrefetchingTermLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );

		$termLookup = new BufferingTermLookup(
			$termIndex, // TODO: customize buffer sizes
			1000
		);

		if ( $settings->useNormalizedPropertyTerms() ) {
			$mediaWikiServices = MediaWikiServices::getInstance();
			$logger = LoggerFactory::getInstance( 'Wikibase' );

			$repoDbDomain = $services->getDatabaseName();
			$loadBalancerFactory = $mediaWikiServices->getDBLoadBalancerFactory();
			$loadBalancer = $loadBalancerFactory->getMainLB( $repoDbDomain );
			$databaseTypeIdsStore = new DatabaseTypeIdsStore(
				$loadBalancer,
				$mediaWikiServices->getMainWANObjectCache(),
				$repoDbDomain,
				$logger
			);

			$propertyTermLookup = new PrefetchingPropertyTermLookup(
				$loadBalancer,
				new DatabaseTermIdsResolver(
					$databaseTypeIdsStore,
					$databaseTypeIdsStore,
					$loadBalancer,
					$repoDbDomain,
					$logger
				),
				$repoDbDomain
			);
			$termLookup = new ByTypeDispatchingPrefetchingTermLookup(
				[ 'property' => $propertyTermLookup ],
				$termLookup
			);
		}

		return $termLookup;
	},

	'PropertyInfoLookup' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices,
		DataAccessSettings $settings
	) {
		return new PropertyInfoTable(
			$services->getEntityIdComposer(),
			new UnusableEntitySource(),
			$settings,
			$services->getDatabaseName(),
			$services->getRepositoryName()
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

	'TermSearchInteractorFactory' => function (
		PerRepositoryServiceContainer $services,
		GenericServices $genericServices
	) {
		/** @var TermIndex $termIndex */
		$termIndex = $services->getService( 'TermIndex' );
		/** @var PrefetchingTermLookup $prefetchingTermLookup */
		$prefetchingTermLookup = $services->getService( 'PrefetchingTermLookup' );

		return new TermIndexSearchInteractorFactory(
			$termIndex,
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
					new UnusableEntitySource(),
					$settings,
					$services->getDatabaseName(),
					$services->getRepositoryName()
				),
				$services->getDatabaseName(),
				$services->getRepositoryName()
			),
			LoggerFactory::getInstance( 'Wikibase' )
		);
	},

];
