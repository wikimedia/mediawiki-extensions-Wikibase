<?php

declare( strict_types=1 );

namespace Wikibase\DataAccess;

use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\NameTableStore;
use Serializers\Serializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
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
	 * @var DatabaseEntitySource
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

	/**
	 * @var RepoDomainDb
	 */
	private $repoDb;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $dataAccessSettings,
		DatabaseEntitySource $entitySource,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		Serializer $storageEntitySerializer,
		RepoDomainDb $repoDb,
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
		$this->repoDb = $repoDb;
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
	): void {
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
	 * @return DatabaseEntitySource The EntitySource object for this set of services
	 */
	public function getEntitySource(): DatabaseEntitySource {
		return $this->entitySource;
	}

	public function getEntityRevisionLookup(): EntityRevisionLookup {
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
				$revisionStoreFactory->getRevisionStore( $databaseName )
			);

			$this->entityRevisionLookup = new TypeDispatchingEntityRevisionLookup(
				$this->entityRevisionLookupFactoryCallbacks,
				$wikiPageEntityRevisionStoreLookup
			);
		}

		return $this->entityRevisionLookup;
	}

	private function getEntityDeserializer(): Deserializer {
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

	private function getEntityMetaDataAccessor(): PrefetchingWikiPageEntityMetaDataAccessor {
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
						$this->repoDb,
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

	public function getEntityPrefetcher(): EntityPrefetcher {
		return $this->getEntityMetaDataAccessor();
	}

	public function getPropertyInfoLookup(): PropertyInfoLookup {
		if ( !in_array( Property::ENTITY_TYPE, $this->entitySource->getEntityTypes() ) ) {
			throw new \LogicException( 'Entity source: ' . $this->entitySource->getSourceName() . ' does no provide properties' );
		}
		if ( $this->propertyInfoLookup === null ) {
			$this->propertyInfoLookup = new PropertyInfoTable(
				$this->entityIdComposer,
				$this->repoDb,
				false
			);
		}
		return $this->propertyInfoLookup;
	}

	public function entityUpdated( EntityRevision $entityRevision ): void {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->entityUpdated( $entityRevision );
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ): void {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	public function entityDeleted( EntityId $entityId ): void {
		// TODO: should this become more "generic" and somehow enumerate all services and
		// update all of these which are instances of EntityStoreWatcher?

		// Only notify entityMetaDataAccessor if the service is created, as the EntityStoreWatcher
		// is only used for purging of an in process cache.
		if ( $this->entityMetaDataAccessor !== null ) {
			$this->entityMetaDataAccessor->entityDeleted( $entityId );
		}
	}

}
