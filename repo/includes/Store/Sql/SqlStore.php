<?php

namespace Wikibase;

use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use Revision;
use Hooks;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\CacheRetrievingEntityRevisionLookup;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\CacheAwarePropertyInfoStore;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\TypeDispatchingEntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
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
use Wikimedia\Rdbms\DBQueryError;
use WikiPage;

/**
 * Implementation of the store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlStore implements Store {

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
	 * @var CacheRetrievingEntityRevisionLookup|null
	 */
	private $cacheRetrievingEntityRevisionLookup = null;

	/**
	 * @var EntityStore|null
	 */
	private $entityStore = null;

	/**
	 * @var DispatchingEntityStoreWatcher|null
	 */
	private $entityStoreWatcher = null;

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
	 * @var WikibaseServices
	 */
	private $wikibaseServices;

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
	 * @var array[]
	 */
	private $idBlacklist;

	/**
	 * @var bool
	 */
	private $useSearchFields;

	/**
	 * @var bool
	 */
	private $forceWriteSearchFields;

	/**
	 * @param EntityChangeFactory $entityChangeFactory
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityIdLookup $entityIdLookup
	 * @param EntityTitleStoreLookup $entityTitleLookup
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param WikibaseServices $wikibaseServices Service container providing data access services
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		EntityIdLookup $entityIdLookup,
		EntityTitleStoreLookup $entityTitleLookup,
		EntityNamespaceLookup $entityNamespaceLookup,
		WikibaseServices $wikibaseServices
	) {
		$this->entityChangeFactory = $entityChangeFactory;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->entityIdLookup = $entityIdLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->wikibaseServices = $wikibaseServices;

		//TODO: inject settings
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->idBlacklist = $settings->getSetting( 'idBlacklist' );
		$this->useSearchFields = $settings->getSetting( 'useTermsTableSearchFields' );
		$this->forceWriteSearchFields = $settings->getSetting( 'forceWriteTermsTableSearchFields' );
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
		$termSqlIndex = new TermSqlIndex(
			$stringNormalizer,
			$this->entityIdComposer,
			$this->entityIdParser,
			false,
			''
		);
		$termSqlIndex->setUseSearchFields( $this->useSearchFields );
		$termSqlIndex->setForceWriteSearchFields( $this->forceWriteSearchFields );

		return $termSqlIndex;
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
			[ 'page' ],
			[ 'page_id', 'page_latest' ],
			[ 'page_content_model' => WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getEntityContentModels() ],
			__METHOD__,
			[ 'LIMIT' => 1000 ] // TODO: continuation
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
		return new SqlIdGenerator(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			$this->idBlacklist
		);
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
	 * @see Store::getEntityByLinkedTitleLookup
	 *
	 * @return EntityByLinkedTitleLookup
	 */
	public function getEntityByLinkedTitleLookup() {
		$lookup = $this->newSiteLinkStore();

		Hooks::run( 'GetEntityByLinkedTitleLookup', [ &$lookup ] );

		return $lookup;
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
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
	}

	/**
	 * @see Store::getEntityLookup
	 * @see SqlStore::getEntityRevisionLookup
	 *
	 * The EntityLookup returned by this method will resolve redirects.
	 *
	 * @param string $cache Flag string: Can be set to 'uncached' to get an uncached direct lookup or to 'retrieve-only' to get a
	 *        lookup which reads from the cache, but doesn't store retrieved entities there. Defaults to a caching lookup.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $cache = '' ) {
		$revisionLookup = $this->getEntityRevisionLookup( $cache );
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
	 * @return EntityStore
	 */
	private function newEntityStore() {
		$contentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$idGenerator = $this->newIdGenerator();

		$store = new WikiPageEntityStore( $contentFactory, $idGenerator, $this->entityIdComposer );
		$store->registerWatcher( $this->getEntityStoreWatcher() );

		$store = new TypeDispatchingEntityStore(
			WikibaseRepo::getDefaultInstance()->getEntityStoreFactoryCallbacks(),
			$store,
			$this->getEntityRevisionLookup( 'uncached' )
		);

		return $store;
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @param string $cache Flag string: Can be set to 'uncached' to get an uncached direct lookup or to 'retrieve-only' to get a
	 *        lookup which reads from the cache, but doesn't store retrieved entities there. Defaults to a caching lookup.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $cache = '' ) {
		if ( !$this->entityRevisionLookup ) {
			list( $this->rawEntityRevisionLookup, $this->entityRevisionLookup ) = $this->newEntityRevisionLookup();
		}

		if ( $cache === 'uncached' ) {
			return $this->rawEntityRevisionLookup;
		} elseif ( $cache === 'retrieve-only' ) {
			return $this->getCacheRetrievingEntityRevisionLookup();
		} else {
			return $this->entityRevisionLookup;
		}
	}

	/**
	 * @return string
	 */
	private function getEntityRevisionLookupCacheKey() {
		// NOTE: Keep cache key in sync with DirectSqlStore::newEntityRevisionLookup in WikibaseClient
		return $this->cacheKeyPrefix . ':WikiPageEntityRevisionLookup';
	}

	/**
	 * Creates a strongly connected pair of EntityRevisionLookup services, the first being the
	 * non-caching lookup, the second being the caching lookup.
	 *
	 * @return EntityRevisionLookup[] A two-element array with a "raw", non-caching and a caching
	 *  EntityRevisionLookup.
	 */
	private function newEntityRevisionLookup() {
		// Maintain a list of watchers to be notified of changes to any entities,
		// in order to update caches.
		/** @var WikiPageEntityStore $dispatcher */
		$dispatcher = $this->getEntityStoreWatcher();

		$dispatcher->registerWatcher( $this->wikibaseServices->getEntityStoreWatcher() );
		$nonCachingLookup = $this->wikibaseServices->getEntityRevisionLookup();

		$nonCachingLookup = new TypeDispatchingEntityRevisionLookup(
			WikibaseRepo::getDefaultInstance()->getEntityRevisionLookupFactoryCallbacks(),
			$nonCachingLookup
		);

		// Lower caching layer using persistent cache (e.g. memcached).
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache(
				wfGetCache( $this->cacheType ),
				$this->cacheDuration,
				$this->getEntityRevisionLookupCacheKey()
			),
			$nonCachingLookup
		);
		// We need to verify the revision ID against the database to avoid stale data.
		$persistentCachingLookup->setVerifyRevision( true );
		$dispatcher->registerWatcher( $persistentCachingLookup );

		// Top caching layer using an in-process hash.
		$hashCachingLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache( new HashBagOStuff( [ 'maxKeys' => 1000 ] ) ),
			$persistentCachingLookup
		);
		// No need to verify the revision ID, we'll ignore updates that happen during the request.
		$hashCachingLookup->setVerifyRevision( false );
		$dispatcher->registerWatcher( $hashCachingLookup );

		return [ $nonCachingLookup, $hashCachingLookup ];
	}

	/**
	 * @return CacheRetrievingEntityRevisionLookup
	 */
	private function getCacheRetrievingEntityRevisionLookup() {
		if ( !$this->cacheRetrievingEntityRevisionLookup ) {
			$cacheRetrievingEntityRevisionLookup = new CacheRetrievingEntityRevisionLookup(
				new EntityRevisionCache(
					wfGetCache( $this->cacheType ),
					$this->cacheDuration,
					$this->getEntityRevisionLookupCacheKey()
				),
				$this->getEntityRevisionLookup( 'uncached' )
			);

			$cacheRetrievingEntityRevisionLookup->setVerifyRevision( true );

			$this->cacheRetrievingEntityRevisionLookup = $cacheRetrievingEntityRevisionLookup;
		}

		return $this->cacheRetrievingEntityRevisionLookup;
	}

	/**
	 * @see Store::getEntityInfoBuilder
	 *
	 * @return EntityInfoBuilder
	 */
	public function getEntityInfoBuilder() {
		return $this->wikibaseServices->getEntityInfoBuilder();
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
	 * Note: cache key used by the lookup should be the same as the cache key used
	 * by CachedPropertyInfoStore.
	 *
	 * @return PropertyInfoLookup
	 */
	private function newPropertyInfoLookup() {
		$nonCachingLookup = $this->wikibaseServices->getPropertyInfoLookup();

		$cacheKey = $this->cacheKeyPrefix . ':CacheAwarePropertyInfoStore';

		return new CachingPropertyInfoLookup(
			$nonCachingLookup,
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
		return $this->wikibaseServices->getEntityPrefetcher();
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
		return new SqlChangeStore( MediaWikiServices::getInstance()->getDBLoadBalancer() );
	}

}
