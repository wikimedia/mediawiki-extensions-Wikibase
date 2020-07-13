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
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\BufferingTermIndexTermLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoreDelegatingMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup;
use Wikibase\Lib\WikibaseSettings;
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
	private $dataAccessSettings;

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

	private $termSearchInteractorFactory = null;

	private $termIndex = null;
	private $termIndexPrefetchingTermLookup = null;

	private $prefetchingTermLookup = null;

	/**
	 * @var PrefetchingWikiPageEntityMetaDataAccessor|null
	 */
	private $entityMetaDataAccessor = null;

	private $propertyInfoLookup = null;

	private $entityRevisionLookupFactoryCallbacks;

	public function __construct(
		GenericServices $genericServices,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $dataAccessSettings,
		EntitySource $entitySource,
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks,
		array $prefetchingTermLookupCallbacks,
		array $entityRevisionFactoryLookupCallbacks
	) {
		$this->assertCallbackArrayTypes(
			$deserializerFactoryCallbacks,
			$entityMetaDataAccessorCallbacks,
			$prefetchingTermLookupCallbacks,
			$entityRevisionFactoryLookupCallbacks
		);

		$this->genericServices = $genericServices;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->slotRoleStore = $slotRoleStore;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->entitySource = $entitySource;
		$this->deserializerFactoryCallbacks = $deserializerFactoryCallbacks;
		$this->entityMetaDataAccessorCallbacks = $entityMetaDataAccessorCallbacks;
		$this->prefetchingTermLookupCallbacks = $prefetchingTermLookupCallbacks;
		$this->entityRevisionLookupFactoryCallbacks = $entityRevisionFactoryLookupCallbacks;
	}

	private function assertCallbackArrayTypes(
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks,
		array $prefetchingTermLookupCallbacks,
		array $entityRevisionFactoryLookupCallbacks
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
		Assert::parameterElementType(
			'callable',
			$entityRevisionFactoryLookupCallbacks,
			'$entityRevisionFactoryLookupCallbacks'
		);
	}

	/**
	 * @return EntitySource The EntitySource object for this set of services
	 */
	public function getEntitySource() : EntitySource {
		return $this->entitySource;
	}

	/**
	 * @deprecated This should not be used, and was introduced only to fix an UBN on master.
	 * This is currently used to create a TermStoresDelegatingPrefetchingItemTermLookup,
	 * when that service construction should actually be moved to within this class.
	 * The TermStoresDelegatingPrefetchingItemTermLookup service will be going away once we remove
	 * all wb_terms migration related code, and thus we will remove this method after that point.
	 *
	 * @return DataAccessSettings
	 */
	public function getDataAccessSettings() : DataAccessSettings {
		return $this->dataAccessSettings;
	}

	/**
	 * It would be nice to only return hint against the TermInLangIdsResolver interface here,
	 * but current users need a method only provided by DatabaseTermInLangIdsResolver
	 * @return DatabaseTermInLangIdsResolver
	 */
	public function getTermInLangIdsResolver() : DatabaseTermInLangIdsResolver {
		$mediaWikiServices = MediaWikiServices::getInstance();
		$logger = LoggerFactory::getInstance( 'Wikibase' );

		$databaseName = $this->entitySource->getDatabaseName();
		$loadBalancer = $mediaWikiServices->getDBLoadBalancerFactory()
			->getMainLB( $databaseName );

		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$mediaWikiServices->getMainWANObjectCache(),
			$databaseName,
			$logger
		);
		return new DatabaseTermInLangIdsResolver(
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$loadBalancer,
			$databaseName,
			$logger
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
				$this->dataAccessSettings->maxSerializedEntitySizeInBytes()
			);

			/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
			$metaDataAccessor = $this->getEntityMetaDataAccessor();

			// TODO: instead calling static getInstance randomly here, inject two db-specific services
			$revisionStoreFactory = MediaWikiServices::getInstance()->getRevisionStoreFactory();
			$blobStoreFactory = MediaWikiServices::getInstance()->getBlobStoreFactory();

			$databaseName = $this->entitySource->getDatabaseName();

			// TODO: This wikiPageEntityRevisionStoreLookup should probably instead be built by a factory
			// that is returned by a method somewhere in data-access and then instead of being used here
			// as a default it should go in the wiring files for each entity type. See: T246451
			$wikiPageEntityRevisionStoreLookup = new WikiPageEntityRevisionLookup(
				$metaDataAccessor,
				new WikiPageEntityDataLoader( $codec, $blobStoreFactory->newBlobStore( $databaseName ) ),
				$revisionStoreFactory->getRevisionStore( $databaseName ),
				$databaseName
			);

			$this->entityRevisionLookup = new TypeDispatchingEntityRevisionLookup(
				$this->entityRevisionLookupFactoryCallbacks,
				$wikiPageEntityRevisionStoreLookup
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
						$this->entitySource
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

	public function getTermSearchInteractorFactory(): TermSearchInteractorFactory {
		if ( $this->termSearchInteractorFactory === null ) {
			$delegatingMatchingTermsLookup = new TermStoreDelegatingMatchingTermsLookup(
				$this->getTermIndex(),
				$this->getMatchingTermsLookup(),
				$this->dataAccessSettings->itemSearchMigrationStage(),
				$this->dataAccessSettings->propertySearchMigrationStage()
			);
			$this->termSearchInteractorFactory = new MatchingTermsSearchInteractorFactory(
				$delegatingMatchingTermsLookup,
				$this->genericServices->getLanguageFallbackChainFactory(),
				$this->getPrefetchingTermLookup()
			);
		}

		return $this->termSearchInteractorFactory;
	}

	private function getMatchingTermsLookup(): MatchingTermsLookup {
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
		return new DatabaseMatchingTermsLookup(
			$loadBalancer,
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$this->entityIdComposer,
			$logger
		);
	}

	private function getTermIndex() {
		if ( $this->termIndex === null ) {
			$this->termIndex = new TermSqlIndex(
				$this->genericServices->getStringNormalizer(),
				$this->entityIdParser,
				$this->entitySource
			);

			$this->termIndex->setUseSearchFields( $this->dataAccessSettings->useSearchFields() );
			$this->termIndex->setForceWriteSearchFields( $this->dataAccessSettings->forceWriteSearchFields() );

		}

		return $this->termIndex;
	}

	/**
	 * @deprecated This will be removed once wb_terms related code has been removed from Wikibase
	 * @return BufferingTermIndexTermLookup
	 */
	public function getTermIndexPrefetchingTermLookup() : PrefetchingTermLookup {
		if ( $this->termIndexPrefetchingTermLookup === null ) {

			$this->termIndexPrefetchingTermLookup = new BufferingTermIndexTermLookup(
				$this->getTermIndex(), // TODO: customize buffer sizes
				1000
			);
		}
		return $this->termIndexPrefetchingTermLookup;
	}

	public function getPrefetchingTermLookup() {
		if ( $this->prefetchingTermLookup === null ) {
			$this->prefetchingTermLookup = new ByTypeDispatchingPrefetchingTermLookup(
				$this->getPrefetchingTermLookups(),
				new NullPrefetchingTermLookup()
			);
		}

		return $this->prefetchingTermLookup;
	}

	/**
	 * @return PrefetchingItemTermLookup[] indexed by entity type
	 */
	private function getPrefetchingTermLookups(): array {
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
			$this->propertyInfoLookup = new PropertyInfoTable(
				$this->entityIdComposer,
				$this->entitySource->getDatabaseName(),
				false
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
