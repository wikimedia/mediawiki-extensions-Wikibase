<?php

namespace Wikibase\DataAccess;

use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\NameTableStore;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdMissRecordingSimpleCache;
use Wikibase\Lib\Store\ByIdDispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Store\BufferingTermIndexTermLookup;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\Assert;

/**
 * Collection of services for a single EntitySource.
 * Some GenericServices are injected alongside some more specific services for the EntitySource.
 * Various logic then pulls these services together into more composed services.
 *
 * TODO fixme, lots of things in this class bind to wikibase lib and mediawiki directly.
 *
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServices implements EntityStoreWatcher {

	/**
	 * @var GenericServices
	 */
	private $genericServices;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	private $entityIdComposer;

	private $dataValueDeserializer;

	/**
	 * @var DataAccessSettings
	 */
	private $settings;

	/**
	 * @var EntitySource
	 */
	private $entitySource;
	private $deserializerFactoryCallbacks;
	private $entityMetaDataAccessorCallbacks;

	/**
	 * @var callable[]
	 */
	private $prefetchingTermLookupCallbacks;

	private $slotRoleStore;
	private $entityRevisionLookup = null;

	private $entityInfoBuilder = null;

	private $termSearchInteractorFactory = null;

	private $termIndex = null;

	private $prefetchingTermLookup = null;

	/**
	 * @var PrefetchingWikiPageEntityMetaDataAccessor|null
	 */
	private $entityMetaDataAccessor = null;

	private $propertyInfoLookup = null;

	public function __construct(
		GenericServices $genericServices,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $settings,
		EntitySource $entitySource,
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks,
		array $prefetchingTermLookupCallbacks
	) {
		$this->assertCallbackArrayTypes( $deserializerFactoryCallbacks, $entityMetaDataAccessorCallbacks, $prefetchingTermLookupCallbacks );

		$this->genericServices = $genericServices;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->slotRoleStore = $slotRoleStore;
		$this->settings = $settings;
		$this->entitySource = $entitySource;
		$this->deserializerFactoryCallbacks = $deserializerFactoryCallbacks;
		$this->entityMetaDataAccessorCallbacks = $entityMetaDataAccessorCallbacks;
		$this->prefetchingTermLookupCallbacks = $prefetchingTermLookupCallbacks;
	}

	private function assertCallbackArrayTypes(
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks,
		array $prefetchingTermLookupCallbacks
	) {
		Assert::parameterElementType(
			'callable',
			$deserializerFactoryCallbacks,
			'$deserializerFactoryCallbacks'
		);
		Assert::parameterElementType(
			'callable',
			$entityMetaDataAccessorCallbacks,
			'$entityMetaDataAccessorCallbacks'
		);
		Assert::parameterElementType(
			'callable',
			$prefetchingTermLookupCallbacks,
			'$prefetchingTermLookupCallbacks'
		);
	}

	public function getEntityRevisionLookup() {
		if ( $this->entityRevisionLookup === null ) {
			if ( !WikibaseSettings::isRepoEnabled() ) {
				$serializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
			} else {
				$serializer = $this->genericServices->getStorageEntitySerializer();
			}

			$codec = new EntityContentDataCodec(
				$this->entityIdParser,
				$serializer,
				$this->getEntityDeserializer(),
				$this->settings->maxSerializedEntitySizeInBytes()
			);

			/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
			$metaDataAccessor = $this->getEntityMetaDataAccessor();

			// TODO: instead calling static getInstance randomly here, inject two db-specific services
			$revisionStoreFactory = MediaWikiServices::getInstance()->getRevisionStoreFactory();
			$blobStoreFactory = MediaWikiServices::getInstance()->getBlobStoreFactory();

			$databaseName = $this->entitySource->getDatabaseName();
			$this->entityRevisionLookup = new WikiPageEntityRevisionLookup(
				$metaDataAccessor,
				new WikiPageEntityDataLoader( $codec, $blobStoreFactory->newBlobStore( $databaseName ) ),
				$revisionStoreFactory->getRevisionStore( $databaseName ),
				$databaseName
			);
		}

		return $this->entityRevisionLookup;
	}

	private function getEntityDeserializer() {
		$deserializerFactory = new DeserializerFactory(
			$this->dataValueDeserializer,
			$this->entityIdParser
		);

		$deserializers = [];
		foreach ( $this->deserializerFactoryCallbacks as $callback ) {
			$deserializers[] = call_user_func( $callback, $deserializerFactory );
		}

		$internalDeserializerFactory = new InternalDeserializerFactory(
			$this->dataValueDeserializer,
			$this->entityIdParser,
			new DispatchingDeserializer( $deserializers )
		);

		return $internalDeserializerFactory->newEntityDeserializer();
	}

	private function getEntityMetaDataAccessor() {
		if ( $this->entityMetaDataAccessor === null ) {
			// TODO: Having this lookup in GenericServices seems shady, this class should
			// probably create/provide one for itself (all data needed in in the entity source)
			$entityNamespaceLookup = $this->genericServices->getEntityNamespaceLookup();
			$repositoryName = '';
			$databaseName = $this->entitySource->getDatabaseName();
			$this->entityMetaDataAccessor = new PrefetchingWikiPageEntityMetaDataAccessor(
				new TypeDispatchingWikiPageEntityMetaDataAccessor(
					$this->entityMetaDataAccessorCallbacks,
					new WikiPageEntityMetaDataLookup(
						$entityNamespaceLookup,
						new EntityIdLocalPartPageTableEntityQuery(
							$entityNamespaceLookup,
							$this->slotRoleStore
						),
						$this->entitySource,
						$this->settings,
						$databaseName,
						$repositoryName
					),
					$databaseName,
					$repositoryName
				),
				// TODO: inject?
				LoggerFactory::getInstance( 'Wikibase' )
			);
		}

		return $this->entityMetaDataAccessor;
	}

	public function getEntityInfoBuilder() {
		global $wgSecretKey;

		if ( $this->entityInfoBuilder === null ) {
			// TODO: Having this lookup in GenericServices seems shady, this class should
			// probably create/provide one for itself (all data needed in in the entity source)

			$entityNamespaceLookup = $this->genericServices->getEntityNamespaceLookup();
			$repositoryName = '';
			$databaseName = $this->entitySource->getDatabaseName();

			$cacheSecret = hash( 'sha256', $wgSecretKey );

			$cache = new SimpleCacheWithBagOStuff(
				MediaWikiServices::getInstance()->getLocalServerObjectCache(),
				'wikibase.sqlEntityInfoBuilder.',
				$cacheSecret
			);
			$cache = new StatsdMissRecordingSimpleCache(
				$cache,
				MediaWikiServices::getInstance()->getStatsdDataFactory(),
				'wikibase.sqlEntityInfoBuilder.miss'
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
				$this->entityIdParser,
				$this->entityIdComposer,
				$entityNamespaceLookup,
				$logger,
				$this->entitySource,
				$this->settings,
				$cache,
				$databaseName,
				$repositoryName
			);

			$newEntityInfoBuilder = new DatabaseEntityInfoBuilder(
				$this->entityIdParser,
				$this->entityIdComposer,
				$entityNamespaceLookup,
				$logger,
				$this->entitySource,
				$this->settings,
				$cache,
				$loadBalancer,
				$termIdsResolver,
				$repositoryName,
				$databaseName
			);

			$typeDispatchingMapping = [];

			// Properties
			if ( $this->settings->useNormalizedPropertyTerms() === true ) {
				$typeDispatchingMapping[Property::ENTITY_TYPE] = $newEntityInfoBuilder;
			} else {
				$typeDispatchingMapping[Property::ENTITY_TYPE] = $oldEntityInfoBuilder;
			}

			// Items
			$itemEntityInfoBuilderMapping = [];
			foreach ( $this->settings->getItemTermsMigrationStages() as $maxId => $stage ) {
				if ( $stage >= MIGRATION_WRITE_NEW ) {
					$itemEntityInfoBuilderMapping[$maxId] = $newEntityInfoBuilder;
				} else {
					$itemEntityInfoBuilderMapping[$maxId] = $oldEntityInfoBuilder;
				}
			}
			$typeDispatchingMapping[Item::ENTITY_TYPE] = new ByIdDispatchingEntityInfoBuilder( $itemEntityInfoBuilderMapping );

			$this->entityInfoBuilder = new ByTypeDispatchingEntityInfoBuilder( $typeDispatchingMapping );
		}

		return $this->entityInfoBuilder;
	}

	public function getTermSearchInteractorFactory() {
		if ( $this->termSearchInteractorFactory === null ) {
			$this->termSearchInteractorFactory = new MatchingTermsSearchInteractorFactory(
				$this->getTermIndex(),
				$this->genericServices->getLanguageFallbackChainFactory(),
				$this->getPrefetchingTermLookup()
			);
		}

		return $this->termSearchInteractorFactory;
	}

	private function getTermIndex() {
		if ( $this->termIndex === null ) {
			$repositoryName = '';

			$this->termIndex = new TermSqlIndex(
				$this->genericServices->getStringNormalizer(),
				$this->entityIdComposer,
				$this->entityIdParser,
				$this->entitySource,
				$this->settings,
				$this->entitySource->getDatabaseName(),
				$repositoryName
			);

			$this->termIndex->setUseSearchFields( $this->settings->useSearchFields() );
			$this->termIndex->setForceWriteSearchFields( $this->settings->forceWriteSearchFields() );

		}

		return $this->termIndex;
	}

	public function getPrefetchingTermLookup() {
		global $wgSecretKey;

		if ( $this->prefetchingTermLookup === null ) {
			$termIndex = $this->getTermIndex();

			$termIndexBackedTermLookup = new BufferingTermIndexTermLookup(
				$termIndex, // TODO: customize buffer sizes
				1000
			);

			$mediaWikiServices = MediaWikiServices::getInstance();
			$logger = LoggerFactory::getInstance( 'Wikibase' );

			$repoDbDomain = $this->entitySource->getDatabaseName();
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

			$lookups = [];

			$lookups['item'] = new TermStoresDelegatingPrefetchingItemTermLookup(
				$this->settings,
				new PrefetchingItemTermLookup( $loadBalancer, $termIdsResolver, $repoDbDomain ),
				$termIndexBackedTermLookup
			);

			if ( $this->settings->useNormalizedPropertyTerms() ) {
				$cacheSecret = hash( 'sha256', $wgSecretKey );

				$cache = new SimpleCacheWithBagOStuff(
					MediaWikiServices::getInstance()->getLocalServerObjectCache(),
					'wikibase.prefetchingPropertyTermLookup.',
					$cacheSecret
				);
				$cache = new StatsdMissRecordingSimpleCache(
					$cache,
					MediaWikiServices::getInstance()->getStatsdDataFactory(),
					'wikibase.prefetchingPropertyTermLookupCache.miss'
				);
				$redirectResolvingRevisionLookup = new RedirectResolvingLatestRevisionLookup( $this->getEntityRevisionLookup() );
				$lookups['property'] = new CachingPrefetchingTermLookup(
					$cache,
					new UncachedTermsPrefetcher(
						new PrefetchingPropertyTermLookup( $loadBalancer, $termIdsResolver, $repoDbDomain ),
						$redirectResolvingRevisionLookup,
						60 // 1 minute ttl
					),
					$redirectResolvingRevisionLookup,
					WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
				);
			} else {
				$lookups['property'] = $termIndexBackedTermLookup;
			}

			$lookups = array_merge( $lookups, $this->getCustomPrefetchingTermLookups() );

			$this->prefetchingTermLookup = new ByTypeDispatchingPrefetchingTermLookup( $lookups, new NullPrefetchingTermLookup() );
		}
		return $this->prefetchingTermLookup;
	}

	/**
	 * @return PrefetchingItemTermLookup[] indexed by entity type
	 */
	private function getCustomPrefetchingTermLookups(): array {
		$typesWithCustomLookups = array_keys( $this->prefetchingTermLookupCallbacks );

		$lookupConstructorsByType = array_intersect( $typesWithCustomLookups, $this->entitySource->getEntityTypes() );
		$customLookups = [];
		foreach ( $lookupConstructorsByType as $type ) {
			$callback = $this->prefetchingTermLookupCallbacks[$type];
			$lookup = call_user_func( $callback, $this );

			Assert::postcondition(
				$lookup instanceof PrefetchingTermLookup,
				"Callback creating a lookup for $type must create an instance of PrefetchingTermLookup"
			);

			$customLookups[$type] = $lookup;
		}
		return $customLookups;
	}

	public function getEntityPrefetcher() {
		return $this->getEntityMetaDataAccessor();
	}

	public function getPropertyInfoLookup() {
		if ( !in_array( Property::ENTITY_TYPE, $this->entitySource->getEntityTypes() ) ) {
			throw new \LogicException( 'Entity source: ' . $this->entitySource->getSourceName() . ' does no provide properties' );
		}
		if ( $this->propertyInfoLookup === null ) {
			$irrelevantRepositoryName = '';
			$this->propertyInfoLookup = new PropertyInfoTable(
				$this->entityIdComposer,
				$this->entitySource,
				$this->settings,
				$this->entitySource->getDatabaseName(),
				$irrelevantRepositoryName
			);
		}
		return $this->propertyInfoLookup;
	}

	public function entityUpdated( EntityRevision $entityRevision ) {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->entityUpdated( $entityRevision );
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	public function entityDeleted( EntityId $entityId ) {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->entityDeleted( $entityId );
		}
	}

}
