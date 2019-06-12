<?php

namespace Wikibase\DataAccess;

use DataValues\Deserializers\DataValueDeserializer;
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
use Wikibase\Lib\Interactors\TermIndexSearchInteractorFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\Store\BufferingTermLookup;
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
		DataValueDeserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $settings,
		EntitySource $entitySource,
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks
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

		$this->genericServices = $genericServices;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->slotRoleStore = $slotRoleStore;
		$this->settings = $settings;
		$this->entitySource = $entitySource;
		$this->deserializerFactoryCallbacks = $deserializerFactoryCallbacks;
		$this->entityMetaDataAccessorCallbacks = $entityMetaDataAccessorCallbacks;
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
				$codec,
				$metaDataAccessor,
				$revisionStoreFactory->getRevisionStore( $databaseName ),
				$blobStoreFactory->newBlobStore( $databaseName ),
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
		if ( $this->entityInfoBuilder === null ) {
			// TODO: Having this lookup in GenericServices seems shady, this class should
			// probably create/provide one for itself (all data needed in in the entity source)
			$entityNamespaceLookup = $this->genericServices->getEntityNamespaceLookup();
			$repositoryName = '';
			$databaseName = $this->entitySource->getDatabaseName();

			$this->entityInfoBuilder = new SqlEntityInfoBuilder(
				$this->entityIdParser,
				$this->entityIdComposer,
				$entityNamespaceLookup,
				LoggerFactory::getInstance( 'Wikibase' ),
				$this->entitySource,
				$this->settings,
				$databaseName,
				$repositoryName
			);
		}

		return $this->entityInfoBuilder;
	}

	public function getTermSearchInteractorFactory() {
		if ( $this->termSearchInteractorFactory === null ) {
			$this->termSearchInteractorFactory = new TermIndexSearchInteractorFactory(
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
		if ( $this->prefetchingTermLookup === null ) {
			$this->prefetchingTermLookup = new BufferingTermLookup(
				$this->getTermIndex(),
				1000 // TODO: customize buffer sizes
			);

			if ( $this->settings->useNormalizedPropertyTerms() ) {
				$mediaWikiServices = MediaWikiServices::getInstance();
				$logger = LoggerFactory::getInstance( 'Wikibase' );

				$repoDbDomain = $this->entitySource->getDatabaseName();
				$loadBalancerFactory = $mediaWikiServices->getDBLoadBalancerFactory();
				$loadBalancer = $loadBalancerFactory->getMainLB( $repoDbDomain );

				$propertyTermLookup = new PrefetchingPropertyTermLookup(
					$loadBalancer,
					new DatabaseTermIdsResolver(
						new DatabaseTypeIdsStore(
							$loadBalancer,
							$mediaWikiServices->getMainWANObjectCache(),
							$repoDbDomain,
							$logger
						),
						$loadBalancer,
						$logger
					)
				);
				$this->prefetchingTermLookup = new ByTypeDispatchingPrefetchingTermLookup(
					[ 'property' => $propertyTermLookup ],
					$this->prefetchingTermLookup
				);
			}
		}
		return $this->prefetchingTermLookup;
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
