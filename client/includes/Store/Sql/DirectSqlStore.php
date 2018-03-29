<?php

namespace Wikibase\Client\Store\Sql;

use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\SettingsArray;
use Wikibase\StringNormalizer;
use Wikibase\TermIndex;
use Wikibase\Lib\Store\TermPropertyLabelResolver;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\CachingSiteLinkLookup;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Store\EntityIdLookup;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactoryMulti.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DirectSqlStore implements ClientStore {

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
	 * @var string|bool The symbolic database name of the repo wiki or false for the local wiki.
	 */
	private $repoWiki;

	/**
	 * @var SessionConsistentConnectionManager|null
	 */
	private $repoConnectionManager = null;

	/**
	 * @var SessionConsistentConnectionManager|null
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
	 * @var EntityRevisionLookup|null
	 */
	private $entityRevisionLookup = null;

	/**
	 * @var WikibaseServices
	 */
	private $wikibaseServices = null;

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
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup = null;

	/**
	 * @var PropertyInfoLookup|null
	 */
	private $propertyInfoLookup = null;

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
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string[]
	 */
	private $disabledUsageAspects;

	/**
	 * @var int
	 */
	private $entityUsagePerPageLimit;

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
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param WikibaseServices $wikibaseServices
	 * @param SettingsArray $settings
	 * @param string|bool $repoWiki The symbolic database name of the repo wiki or false for the
	 * local wiki.
	 * @param string $languageCode
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		EntityNamespaceLookup $entityNamespaceLookup,
		WikibaseServices $wikibaseServices,
		SettingsArray $settings,
		$repoWiki = false,
		$languageCode
	) {
		$this->entityChangeFactory = $entityChangeFactory;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->wikibaseServices = $wikibaseServices;
		$this->repoWiki = $repoWiki;
		$this->languageCode = $languageCode;

		// @TODO: split the class so it needs less injection
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->siteId = $settings->getSetting( 'siteGlobalID' );
		$this->disabledUsageAspects = $settings->getSetting( 'disabledUsageAspects' );
		$this->entityUsagePerPageLimit = $settings->getSetting( 'entityUsagePerPageLimit' );
		$this->useSearchFields = $settings->getSetting( 'useTermsTableSearchFields' );
		$this->forceWriteSearchFields = $settings->getSetting( 'forceWriteTermsTableSearchFields' );
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
	 * @return SessionConsistentConnectionManager
	 */
	private function getRepoConnectionManager() {
		if ( $this->repoConnectionManager === null ) {
			$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
			$this->repoConnectionManager = new SessionConsistentConnectionManager(
				$lbFactory->getMainLB( $this->repoWiki ),
				$this->repoWiki
			);
		}

		return $this->repoConnectionManager;
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the local (client) wiki's
	 * database.
	 *
	 * @return SessionConsistentConnectionManager
	 */
	private function getLocalConnectionManager() {
		if ( $this->localConnectionManager === null ) {
			$this->localConnectionManager = new SessionConsistentConnectionManager(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			);
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
			$this->usageTracker = new SqlUsageTracker(
				$this->entityIdParser,
				$connectionManager,
				$this->disabledUsageAspects,
				$this->entityUsagePerPageLimit
			);
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

		$dispatchingLookup = $this->wikibaseServices->getEntityRevisionLookup();

		// Lower caching layer using persistent cache (e.g. memcached).
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache(
				wfGetCache( $this->cacheType ),
				$this->cacheDuration,
				$cacheKeyPrefix
			),
			$dispatchingLookup
		);
		// We need to verify the revision ID against the database to avoid stale data.
		$persistentCachingLookup->setVerifyRevision( true );

		// Top caching layer using an in-process hash.
		$hashCachingLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache( new HashBagOStuff( [ 'maxKeys' => 1000 ] ) ),
			$persistentCachingLookup
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
			$this->termIndex = new TermSqlIndex(
				new StringNormalizer(),
				$this->entityIdComposer,
				$this->entityIdParser,
				$this->repoWiki,
				''
			);
			$this->termIndex->setUseSearchFields( $this->useSearchFields );
			$this->termIndex->setForceWriteSearchFields( $this->forceWriteSearchFields );
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
				MediaWikiServices::getInstance()->getDBLoadBalancer(),
				$this->entityIdParser
			);
		}

		return $this->entityIdLookup;
	}

	/**
	 * @see ClientStore::getDescriptionLookup
	 *
	 * @return DescriptionLookup
	 */
	public function getDescriptionLookup() {
		return new DescriptionLookup( $this->getEntityIdLookup(), $this->getTermIndex() );
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
	 * @see ClientStore::getPropertyInfoLookup
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		if ( $this->propertyInfoLookup === null ) {
			$propertyInfoLookup = $this->wikibaseServices->getPropertyInfoLookup();
			$cacheKey = $this->cacheKeyPrefix . ':CacheAwarePropertyInfoStore';

			$this->propertyInfoLookup = new CachingPropertyInfoLookup(
				$propertyInfoLookup,
				ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration,
				$cacheKey
			);
		}

		return $this->propertyInfoLookup;
	}

	/**
	 * @return PrefetchingWikiPageEntityMetaDataAccessor
	 */
	public function getEntityPrefetcher() {
		return $this->wikibaseServices->getEntityPrefetcher();
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
	 * @return EntityChangeLookup
	 */
	public function getEntityChangeLookup() {
		return new EntityChangeLookup( $this->entityChangeFactory, $this->entityIdParser, $this->repoWiki );
	}

}
