<?php

namespace Wikibase;

use HashBagOStuff;
use ObjectCache;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\CachingSiteLinkLookup;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactoryMulti.
 *
 * @since 0.3
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DirectSqlStore implements ClientStore {

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
	 * @var string|bool The symbolic database name of the repo wiki or false for the local wiki.
	 */
	private $repoWiki;

	/**
	 * @var ConsistentReadConnectionManager|null
	 */
	private $repoConnectionManager = null;

	/**
	 * @var ConsistentReadConnectionManager|null
	 */
	private $localConnectionManager = null;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @var int|string
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var EntityLookup|null
	 */
	private $entityRevisionLookup = null;

	/**
	 * @var PropertyLabelResolver|null
	 */
	private $propertyLabelResolver = null;

	/**
	 * @var TermIndex|null
	 */
	private $termIndex = null;

	/**
	 * @var EntityIdLookup|null
	 */
	private $entityIdLookup = null;

	/**
	 * @var PropertyInfoTable|null
	 */
	private $propertyInfoTable = null;

	/**
	 * @var SiteLinkLookup|null
	 */
	private $siteLinkLookup = null;

	/**
	 * @var UsageTracker|null
	 */
	private $usageTracker = null;

	/**
	 * @var UsageLookup|null
	 */
	private $usageLookup = null;

	/**
	 * @var SubscriptionManager|null
	 */
	private $subscriptionManager = null;

	/**
	 * @var PrefetchingWikiPageEntityMetaDataAccessor|null
	 */
	private $entityPrefetcher = null;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param EntityChangeFactory $entityChangeFactory
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $repoWiki The symbolic database name of the repo wiki or false for the
	 * local wiki.
	 * @param string $languageCode
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityContentDataCodec $contentCodec,
		EntityIdParser $entityIdParser,
		$repoWiki = false,
		$languageCode
	) {
		$this->contentCodec = $contentCodec;
		$this->entityChangeFactory = $entityChangeFactory;
		$this->entityIdParser = $entityIdParser;
		$this->repoWiki = $repoWiki;
		$this->languageCode = $languageCode;

		// @TODO: Inject
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->siteId = $settings->getSetting( 'siteGlobalID' );
	}

	/**
	 * @see ClientStore::getSubscriptionManager
	 *
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager() {
		if ( $this->subscriptionManager === null ) {
			$connectionManager = $this->getRepoConnectionManager();
			$this->subscriptionManager = new SqlSubscriptionManager( $connectionManager );
		}

		return $this->subscriptionManager;
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the repo wiki's
	 * database.
	 *
	 * @return ConsistentReadConnectionManager
	 */
	private function getRepoConnectionManager() {
		if ( $this->repoConnectionManager === null ) {
			$this->repoConnectionManager = new ConsistentReadConnectionManager( wfGetLB( $this->repoWiki ), $this->repoWiki );
		}

		return $this->repoConnectionManager;
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the local (client) wiki's
	 * database.
	 *
	 * @return ConsistentReadConnectionManager
	 */
	private function getLocalConnectionManager() {
		if ( $this->localConnectionManager === null ) {
			$this->localConnectionManager = new ConsistentReadConnectionManager( wfGetLB() );
		}

		return $this->localConnectionManager;
	}

	/**
	 * @return RecentChangesDuplicateDetector
	 */
	public function getRecentChangesDuplicateDetector() {
		return new RecentChangesDuplicateDetector(
			$this->getLocalConnectionManager()
		);
	}

	/**
	 * @see ClientStore::getUsageLookup
	 *
	 * @return UsageLookup
	 */
	public function getUsageLookup() {
		if ( $this->usageLookup === null ) {
			$this->usageLookup = $this->getUsageTracker();
		}

		return $this->usageLookup;
	}

	/**
	 * @see ClientStore::getUsageTracker
	 *
	 * @return SqlUsageTracker
	 */
	public function getUsageTracker() {
		if ( $this->usageTracker === null ) {
			$connectionManager = $this->getLocalConnectionManager();
			$this->usageTracker = new SqlUsageTracker( $this->entityIdParser, $connectionManager );
		}

		return $this->usageTracker;
	}

	/**
	 * @see ClientStore::getSiteLinkLookup
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkLookup() {
		if ( $this->siteLinkLookup === null ) {
			$this->siteLinkLookup = new CachingSiteLinkLookup(
				new SiteLinkTable( 'wb_items_per_site', true, $this->repoWiki ),
				new HashBagOStuff()
			);
		}

		return $this->siteLinkLookup;
	}

	/**
	 * @see ClientStore::getEntityLookup
	 *
	 * The EntityLookup returned by this method will resolve redirects.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		$revisionLookup = $this->getEntityRevisionLookup();
		$revisionBasedLookup = new RevisionBasedEntityLookup( $revisionLookup );
		$resolvingLookup = new RedirectResolvingEntityLookup( $revisionBasedLookup );
		return $resolvingLookup;
	}

	/**
	 * @see ClientStore::getEntityRevisionLookup
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		if ( $this->entityRevisionLookup === null ) {
			$this->entityRevisionLookup = $this->newEntityRevisionLookup();
		}

		return $this->entityRevisionLookup;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function newEntityRevisionLookup() {
		// NOTE: Keep cache key in sync with SqlStore::newEntityRevisionLookup in WikibaseRepo
		$cacheKeyPrefix = $this->cacheKeyPrefix . ':WikiPageEntityRevisionLookup';

		$metaDataFetcher = $this->getEntityPrefetcher();
		$rawLookup = new WikiPageEntityRevisionLookup(
			$this->contentCodec,
			$metaDataFetcher,
			$this->repoWiki
		);

		// Lower caching layer using persistent cache (e.g. memcached).
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			$rawLookup,
			wfGetCache( $this->cacheType ),
			$this->cacheDuration,
			$cacheKeyPrefix
		);
		// We need to verify the revision ID against the database to avoid stale data.
		$persistentCachingLookup->setVerifyRevision( true );

		// Top caching layer using an in-process hash.
		$hashCachingLookup = new CachingEntityRevisionLookup(
			$persistentCachingLookup,
			new HashBagOStuff( [ 'maxKeys' => 1000 ] )
		);
		// No need to verify the revision ID, we'll ignore updates that happen during the request.
		$hashCachingLookup->setVerifyRevision( false );

		return $hashCachingLookup;
	}

	/**
	 * @see ClientStore::getTermIndex
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		if ( $this->termIndex === null ) {
			// TODO: Get StringNormalizer from WikibaseClient?
			// Can't really pass this via the constructor...
			$this->termIndex = new TermSqlIndex( new StringNormalizer(), $this->repoWiki );
		}

		return $this->termIndex;
	}

	/**
	 * @see ClientStore::getEntityIdLookup
	 *
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		if ( $this->entityIdLookup === null ) {
			$this->entityIdLookup = new PagePropsEntityIdLookup(
				wfGetLB(),
				$this->entityIdParser
			);
		}

		return $this->entityIdLookup;
	}

	/**
	 * @see ClientStore::getPropertyLabelResolver
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver() {
		if ( $this->propertyLabelResolver === null ) {
			// Cache key needs to be language specific
			$cacheKey = $this->cacheKeyPrefix . ':TermPropertyLabelResolver' . '/' . $this->languageCode;

			$this->propertyLabelResolver = new TermPropertyLabelResolver(
				$this->languageCode,
				$this->getTermIndex(),
				ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration,
				$cacheKey
			);
		}

		return $this->propertyLabelResolver;
	}

	/**
	 * @see ClientStore::clear
	 *
	 * Does nothing.
	 */
	public function clear() {
		// noop
	}

	/**
	 * @see ClientStore::rebuild
	 *
	 * Does nothing.
	 */
	public function rebuild() {
		$this->clear();
	}

	/**
	 * @see ClientStore::getPropertyInfoStore
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( $this->propertyInfoTable === null ) {
			$propertyInfoStore = new PropertyInfoTable( true, $this->repoWiki );
			$cacheKey = $this->cacheKeyPrefix . ':CachingPropertyInfoStore';

			$this->propertyInfoTable = new CachingPropertyInfoStore(
				$propertyInfoStore,
				ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration,
				$cacheKey
			);
		}

		return $this->propertyInfoTable;
	}

	/**
	 * @return PrefetchingWikiPageEntityMetaDataAccessor
	 */
	public function getEntityPrefetcher() {
		if ( $this->entityPrefetcher === null ) {
			$this->entityPrefetcher = new PrefetchingWikiPageEntityMetaDataAccessor(
				new WikiPageEntityMetaDataLookup(
					$this->entityIdParser,
					$this->repoWiki
				)
			);
		}

		return $this->entityPrefetcher;
	}

	/**
	 * @return UsageUpdater
	 */
	public function getUsageUpdater() {
		return new UsageUpdater(
			$this->siteId,
			$this->getUsageTracker(),
			$this->getUsageLookup(),
			$this->getSubscriptionManager()
		);
	}

	/**
	 * @since 0.5
	 *
	 * @return EntityChangeLookup
	 */
	public function getEntityChangeLookup() {
		return new EntityChangeLookup( $this->entityChangeFactory, $this->entityIdParser, $this->repoWiki );
	}

}
