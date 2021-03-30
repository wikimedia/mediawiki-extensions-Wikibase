<?php

namespace Wikibase\DataAccess;

use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\NameTableStore;
use Serializers\Serializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Interactors\MatchingTermsSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup;
use Wikimedia\Assert\Assert;

/**
 * Collection of services for a single EntitySource.
 * Some generic services are injected alongside some more specific services for the EntitySource.
 * Various logic then pulls these services together into more composed services.
 *
 * TODO fixme, lots of things in this class bind to wikibase lib and mediawiki directly.
 *
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServices implements EntityStoreWatcher {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/** @var EntityIdComposer */
	private $entityIdComposer;

	/** @var Deserializer */
	private $dataValueDeserializer;

	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;

	/**
	 * @var EntitySource
	 */
	private $entitySource;

	/** @var LanguageFallbackChainFactory */
	private $languageFallbackChainFactory;

	/** @var callable[] */
	private $deserializerFactoryCallbacks;

	/** @var callable[] */
	private $entityMetaDataAccessorCallbacks;

	/**
	 * @var callable[]
	 */
	private $prefetchingTermLookupCallbacks;

	/** @var NameTableStore */
	private $slotRoleStore;

	/** @var EntityRevisionLookup|null */
	private $entityRevisionLookup = null;

	/** @var TermSearchInteractorFactory|null */
	private $termSearchInteractorFactory = null;

	/** @var PrefetchingTermLookup|null */
	private $prefetchingTermLookup = null;

	/**
	 * @var PrefetchingWikiPageEntityMetaDataAccessor|null
	 */
	private $entityMetaDataAccessor = null;

	/** @var PropertyInfoLookup|null */
	private $propertyInfoLookup = null;

	/** @var callable[] */
	private $entityRevisionLookupFactoryCallbacks;

	/** @var Serializer */
	private $storageEntitySerializer;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $dataAccessSettings,
		EntitySource $entitySource,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		Serializer $storageEntitySerializer,
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

		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->slotRoleStore = $slotRoleStore;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->entitySource = $entitySource;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->storageEntitySerializer = $storageEntitySerializer;
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
	public function getEntitySource(): EntitySource {
		return $this->entitySource;
	}

	/**
	 * It would be nice to only return hint against the TermInLangIdsResolver interface here,
	 * but current users need a method only provided by DatabaseTermInLangIdsResolver
	 * @return DatabaseTermInLangIdsResolver
	 */
	public function getTermInLangIdsResolver(): DatabaseTermInLangIdsResolver {
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
			$codec = new EntityContentDataCodec(
				$this->entityIdParser,
				$this->storageEntitySerializer,
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
				new WikiPageEntityDataLoader( $codec, $blobStoreFactory->newBlobStore( $databaseName ), $databaseName ),
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
			$entityNamespaceLookup = new EntityNamespaceLookup(
				$this->entitySource->getEntityNamespaceIds(),
				$this->entitySource->getEntitySlotNames()
			);
			$repositoryName = '';
			$databaseName = $this->entitySource->getDatabaseName();
			$logger = LoggerFactory::getInstance( 'Wikibase' ); // TODO inject
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
						$logger
					),
					$databaseName,
					$repositoryName
				),
				$logger
			);
		}

		return $this->entityMetaDataAccessor;
	}

	public function getTermSearchInteractorFactory(): TermSearchInteractorFactory {
		if ( $this->termSearchInteractorFactory === null ) {
			$this->termSearchInteractorFactory = new MatchingTermsSearchInteractorFactory(
				$this->getMatchingTermsLookup(),
				$this->languageFallbackChainFactory,
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
