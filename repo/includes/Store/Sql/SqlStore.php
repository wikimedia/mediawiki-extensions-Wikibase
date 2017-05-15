<?php

namespace Wikibase;

use DBQueryError;
use HashBagOStuff;
use ObjectCache;
use Revision;
use Wikibase\Client\EntityDataRetrievalServiceFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\CacheAwarePropertyInfoStore;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilderFactory;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\Repo\Store\DispatchingEntityStoreWatcher;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Store\Sql\SqlEntitiesWithoutTermFinder;
use Wikibase\Repo\Store\Sql\SqlChangeStore;
use Wikibase\Repo\Store\Sql\SqlItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\Repo\Store\Sql\WikiPageEntityRedirectLookup;
use Wikibase\Repo\Store\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;
use WikiPage;

/**
 * Implementation of the store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlStore implements Store {

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var EntityChangeFactory
	 */
	private $entityChangeFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var EntityRevisionLookup|null
	 */
	private $entityRevisionLookup = null;

	/**
	 * @var EntityRevisionLookup|null
	 */
	private $rawEntityRevisionLookup = null;

	/**
	 * @var EntityStore|null
	 */
	private $entityStore = null;

	/**
	 * @var DispatchingEntityStoreWatcher|null
	 */
	private $entityStoreWatcher = null;

	/**
	 * @var EntityInfoBuilderFactory|null
	 */
	private $entityInfoBuilderFactory = null;

	/**
	 * @var PropertyInfoLookup|null
	 */
	private $propertyInfoLookup = null;

	/**
	 * @var PropertyInfoStore|null
	 */
	private $propertyInfoStore = null;

	/**
	 * @var PropertyInfoTable|null
	 */
	private $propertyInfoTable = null;

	/**
	 * @var string|bool false for local, or a database id that wfGetLB understands.
	 */
	private $changesDatabase;

	/**
	 * @var TermIndex|null
	 */
	private $termIndex = null;

	/**
	 * @var PrefetchingWikiPageEntityMetaDataAccessor|null
	 */
	private $entityPrefetcher = null;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityDataRetrievalServiceFactory|null
	 */
	private $entityDataRetrievalServiceFactory = null;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var int[]
	 */
	private $idBlacklist;

	/**
	 * @var bool
	 */
	private $hasFullEntityIdColumn;

	/**
	 * @param EntityChangeFactory $entityChangeFactory
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityTitleStoreLookup $entityTitleLookup
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param EntityDataRetrievalServiceFactory|null $entityDataRetrievalServiceFactory Optional
	 *        service factory providing services configured for the configured repositories
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityContentDataCodec $contentCodec,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		EntityIdLookup $entityIdLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityDataRetrievalServiceFactory $entityDataRetrievalServiceFactory = null
	) {
		$this->entityChangeFactory = $entityChangeFactory;
		$this->contentCodec = $contentCodec;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->entityIdLookup = $entityIdLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityDataRetrievalServiceFactory = $entityDataRetrievalServiceFactory;

		//TODO: inject settings
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->changesDatabase = $settings->getSetting( 'changesDatabase' );
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->idBlacklist = $settings->getSetting( 'idBlacklist' );
		$this->hasFullEntityIdColumn = $settings->getSetting( 'hasFullEntityIdColumn' );
	}

	/**
	 * @see Store::getTermIndex
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		if ( !$this->termIndex ) {
			$this->termIndex = $this->newTermIndex();
		}

		return $this->termIndex;
	}

	/**
	 * @see Store::getLabelConflictFinder
	 *
	 * @return LabelConflictFinder
	 */
	public function getLabelConflictFinder() {
		return $this->getTermIndex();
	}

	/**
	 * @return TermIndex
	 */
	private function newTermIndex() {
		//TODO: Get $stringNormalizer from WikibaseRepo?
		//      Can't really pass this via the constructor...
		$stringNormalizer = new StringNormalizer();
		return new TermSqlIndex(
			$stringNormalizer,
			$this->entityIdComposer,
			false,
			'',
			$this->hasFullEntityIdColumn
		);
	}

	/**
	 * @see Store::clear
	 */
	public function clear() {
		$this->newSiteLinkStore()->clear();
		$this->getTermIndex()->clear();
	}

	/**
	 * @see Store::rebuild
	 */
	public function rebuild() {
		$dbw = wfGetDB( DB_MASTER );

		// TODO: refactor selection code out (relevant for other stores)

		$pages = $dbw->select(
			array( 'page' ),
			array( 'page_id', 'page_latest' ),
			array( 'page_content_model' => WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getEntityContentModels() ),
			__METHOD__,
			array( 'LIMIT' => 1000 ) // TODO: continuation
		);

		foreach ( $pages as $pageRow ) {
			$page = WikiPage::newFromID( $pageRow->page_id );
			$revision = Revision::newFromId( $pageRow->page_latest );
			try {
				$page->doEditUpdates( $revision, $GLOBALS['wgUser'] );
			} catch ( DBQueryError $e ) {
				wfLogWarning(
					'editUpdateFailed for ' . $page->getId() . ' on revision ' .
					$revision->getId() . ': ' . $e->getMessage()
				);
			}
		}
	}

	/**
	 * @see Store::newIdGenerator
	 *
	 * @return IdGenerator
	 */
	public function newIdGenerator() {
		return new SqlIdGenerator( wfGetLB(), $this->idBlacklist );
	}

	/**
	 * @see Store::newSiteLinkStore
	 *
	 * @return SiteLinkStore
	 */
	public function newSiteLinkStore() {
		return new SiteLinkTable( 'wb_items_per_site', false );
	}

	/**
	 * @see Store::newEntitiesWithoutTermFinder
	 *
	 * @return EntitiesWithoutTermFinder
	 */
	public function newEntitiesWithoutTermFinder() {
		return new SqlEntitiesWithoutTermFinder(
			$this->entityIdParser,
			$this->entityNamespaceLookup,
			[ // TODO: Make this configurable!
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P'
			]
		);
	}

	/**
	 * @see Store::newItemsWithoutSitelinksFinder
	 *
	 * @return ItemsWithoutSitelinksFinder
	 */
	public function newItemsWithoutSitelinksFinder() {
		return new SqlItemsWithoutSitelinksFinder(
			$this->entityNamespaceLookup
		);
	}

	/**
	 * @return EntityRedirectLookup
	 */
	public function getEntityRedirectLookup() {
		return new WikiPageEntityRedirectLookup(
			$this->entityTitleLookup,
			$this->entityIdLookup,
			wfGetLB()
		);
	}

	/**
	 * @see Store::getEntityLookup
	 * @see SqlStore::getEntityRevisionLookup
	 *
	 * The EntityLookup returned by this method will resolve redirects.
	 *
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $uncached = '' ) {
		$revisionLookup = $this->getEntityRevisionLookup( $uncached );
		$revisionBasedLookup = new RevisionBasedEntityLookup( $revisionLookup );
		$resolvingLookup = new RedirectResolvingEntityLookup( $revisionBasedLookup );
		return $resolvingLookup;
	}

	/**
	 * @see Store::getEntityStoreWatcher
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher() {
		if ( !$this->entityStoreWatcher ) {
			$this->entityStoreWatcher = new DispatchingEntityStoreWatcher();
		}

		return $this->entityStoreWatcher;
	}

	/**
	 * @see Store::getEntityStore
	 *
	 * @return EntityStore
	 */
	public function getEntityStore() {
		if ( !$this->entityStore ) {
			$this->entityStore = $this->newEntityStore();
		}

		return $this->entityStore;
	}

	/**
	 * @return WikiPageEntityStore
	 */
	private function newEntityStore() {
		$contentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$idGenerator = $this->newIdGenerator();

		$store = new WikiPageEntityStore( $contentFactory, $idGenerator );
		$store->registerWatcher( $this->getEntityStoreWatcher() );
		return $store;
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $uncached = '' ) {
		if ( !$this->entityRevisionLookup ) {
			list( $this->rawEntityRevisionLookup, $this->entityRevisionLookup ) = $this->newEntityRevisionLookup();
		}

		if ( $uncached === 'uncached' ) {
			return $this->rawEntityRevisionLookup;
		} else {
			return $this->entityRevisionLookup;
		}
	}

	/**
	 * Creates a strongly connected pair of EntityRevisionLookup services, the first being the
	 * non-caching lookup, the second being the caching lookup.
	 *
	 * @return EntityRevisionLookup[] A two-element array with a "raw", non-caching and a caching
	 *  EntityRevisionLookup.
	 */
	private function newEntityRevisionLookup() {
		// NOTE: Keep cache key in sync with DirectSqlStore::newEntityRevisionLookup in WikibaseClient
		$cacheKeyPrefix = $this->cacheKeyPrefix . ':WikiPageEntityRevisionLookup';

		// Maintain a list of watchers to be notified of changes to any entities,
		// in order to update caches.
		/** @var WikiPageEntityStore $dispatcher */
		$dispatcher = $this->getEntityStoreWatcher();

		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			// Use entityDataRetrievalServiceFactory as a watcher for entity changes,
			// so that caches of services provided are updated when necessary.
			$dispatcher->registerWatcher( $this->entityDataRetrievalServiceFactory );
			$nonCachingLookup = $this->entityDataRetrievalServiceFactory->getEntityRevisionLookup();
		} else {
			// Watch for entity changes
			$metaDataFetcher = $this->getEntityPrefetcher();
			$dispatcher->registerWatcher( $metaDataFetcher );
			$nonCachingLookup = $this->getRawEntityRevisionLookup( $metaDataFetcher );
		}

		// Lower caching layer using persistent cache (e.g. memcached).
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			$nonCachingLookup,
			wfGetCache( $this->cacheType ),
			$this->cacheDuration,
			$cacheKeyPrefix
		);
		// We need to verify the revision ID against the database to avoid stale data.
		$persistentCachingLookup->setVerifyRevision( true );
		$dispatcher->registerWatcher( $persistentCachingLookup );

		// Top caching layer using an in-process hash.
		$hashCachingLookup = new CachingEntityRevisionLookup(
			$persistentCachingLookup,
			new HashBagOStuff( [ 'maxKeys' => 1000 ] )
		);
		// No need to verify the revision ID, we'll ignore updates that happen during the request.
		$hashCachingLookup->setVerifyRevision( false );
		$dispatcher->registerWatcher( $hashCachingLookup );

		return array( $nonCachingLookup, $hashCachingLookup );
	}

	private function getRawEntityRevisionLookup( WikiPageEntityMetaDataAccessor $metaDataFetcher ) {
		return new WikiPageEntityRevisionLookup(
			$this->contentCodec,
			$metaDataFetcher,
			false
		);
	}

	/**
	 * @see Store::getEntityInfoBuilderFactory
	 *
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory() {
		if ( !$this->entityInfoBuilderFactory ) {
			$this->entityInfoBuilderFactory = $this->newEntityInfoBuilderFactory();
		}

		return $this->entityInfoBuilderFactory;
	}

	/**
	 * Creates a new EntityInfoBuilderFactory
	 *
	 * @return EntityInfoBuilderFactory
	 */
	private function newEntityInfoBuilderFactory() {
		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			return $this->entityDataRetrievalServiceFactory->getEntityInfoBuilderFactory();
		}

		return new SqlEntityInfoBuilderFactory(
			$this->entityIdParser,
			$this->entityIdComposer,
			$this->entityNamespaceLookup
		);
	}

	/**
	 * @see Store::getPropertyInfoLookup
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		if ( !$this->propertyInfoLookup ) {
			$this->propertyInfoLookup = $this->newPropertyInfoLookup();
		}

		return $this->propertyInfoLookup;
	}

	/**
	 * Creates a new PropertyInfoLookup instance
	 * Note: cache key used by the lookup should be the same as the cache key used
	 * by CachedPropertyInfoStore.
	 *
	 * @return PropertyInfoLookup
	 */
	private function newPropertyInfoLookup() {
		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			$table = $this->entityDataRetrievalServiceFactory->getPropertyInfoLookup();
		} else {
			$table = $this->getPropertyInfoTable();
		}

		$cacheKey = $this->cacheKeyPrefix . ':CacheAwarePropertyInfoStore';

		return new CachingPropertyInfoLookup(
			$table,
			ObjectCache::getInstance( $this->cacheType ),
			$this->cacheDuration,
			$cacheKey
		);
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( !$this->propertyInfoStore ) {
			$this->propertyInfoStore = $this->newPropertyInfoStore();
		}

		return $this->propertyInfoStore;
	}

	/**
	 * Creates a new PropertyInfoStore
	 * Note: cache key used by the lookup should be the same as the cache key used
	 * by CachedPropertyInfoLookup.
	 *
	 * @return PropertyInfoStore
	 */
	private function newPropertyInfoStore() {
		// TODO: this should be changed so it uses the same PropertyInfoTable instance which is used by
		// the lookup configured for local repo in DispatchingPropertyInfoLookup (if using dispatching services
		// from client). As we don't want to introduce DispatchingPropertyInfoStore service, this should probably
		// be accessing RepositorySpecificServices of local repo (which is currently not exposed
		// to/by WikibaseClient).
		// For non-dispatching-service use case it is already using the same PropertyInfoTable instance
		// for both store and lookup - no change needed here.

		$table = $this->getPropertyInfoTable();
		$cacheKey = $this->cacheKeyPrefix . ':CacheAwarePropertyInfoStore';

		// TODO: we might want to register the CacheAwarePropertyInfoLookup instance created by
		// newPropertyInfoLookup as a watcher to this CacheAwarePropertyInfoStore instance.
		return new CacheAwarePropertyInfoStore(
			$table,
			ObjectCache::getInstance( $this->cacheType ),
			$this->cacheDuration,
			$cacheKey
		);
	}

	/**
	 * @return PropertyInfoTable
	 */
	private function getPropertyInfoTable() {
		if ( $this->propertyInfoTable === null ) {
			$this->propertyInfoTable = new PropertyInfoTable( $this->entityIdComposer );
		}
		return $this->propertyInfoTable;
	}

	/**
	 * @return SiteLinkConflictLookup
	 */
	public function getSiteLinkConflictLookup() {
		return new SqlSiteLinkConflictLookup( $this->entityIdComposer );
	}

	/**
	 * @return PrefetchingWikiPageEntityMetaDataAccessor
	 */
	public function getEntityPrefetcher() {
		if ( $this->entityPrefetcher === null ) {
			$this->entityPrefetcher = $this->newEntityPrefetcher();
		}

		return $this->entityPrefetcher;
	}

	/**
	 * @return EntityPrefetcher
	 */
	private function newEntityPrefetcher() {
		if ( $this->entityDataRetrievalServiceFactory !== null ) {
			return $this->entityDataRetrievalServiceFactory->getEntityPrefetcher();
		}
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new WikiPageEntityMetaDataLookup( $this->entityNamespaceLookup )
		);
	}

	/**
	 * @return EntityChangeLookup
	 */
	public function getEntityChangeLookup() {
		return new EntityChangeLookup( $this->entityChangeFactory, $this->entityIdParser );
	}

	/**
	 * @return SqlChangeStore
	 */
	public function getChangeStore() {
		return new SqlChangeStore( wfGetLB() );
	}

}
