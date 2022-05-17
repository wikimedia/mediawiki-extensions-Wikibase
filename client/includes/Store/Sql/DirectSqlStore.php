<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Store\Sql;

use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\ImplicitDescriptionUsageLookup;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\CachingSiteLinkLookup;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityRevisionCache;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

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
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var RepoDomainDb
	 */
	private $repoDb;

	/**
	 * @var ClientDomainDb
	 */
	private $clientDb;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @var string
	 */
	private $cacheKeyGroup;

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
	private $wikibaseServices;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

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
	 * @var int
	 */
	private $addEntityUsagesBatchSize;

	/** @var bool */
	private $enableImplicitDescriptionUsage;

	/** @var bool */
	private $allowLocalShortDesc;

	/**
	 * @var TermBuffer
	 */
	private $termBuffer;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdLookup $entityIdLookup,
		WikibaseServices $wikibaseServices,
		SettingsArray $settings,
		TermBuffer $termBuffer,
		RepoDomainDb $repoDb,
		ClientDomainDb $clientDb
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityIdLookup = $entityIdLookup;
		$this->wikibaseServices = $wikibaseServices;
		$this->termBuffer = $termBuffer;
		$this->repoDb = $repoDb;
		$this->clientDb = $clientDb;

		// @TODO: split the class so it needs less injection
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheKeyGroup = $settings->getSetting( 'sharedCacheKeyGroup' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->siteId = $settings->getSetting( 'siteGlobalID' );
		$this->disabledUsageAspects = $settings->getSetting( 'disabledUsageAspects' );
		$this->entityUsagePerPageLimit = $settings->getSetting( 'entityUsagePerPageLimit' );
		$this->addEntityUsagesBatchSize = $settings->getSetting( 'addEntityUsagesBatchSize' );
		$this->enableImplicitDescriptionUsage = $settings->getSetting( 'enableImplicitDescriptionUsage' );
		$this->allowLocalShortDesc = $settings->getSetting( 'allowLocalShortDesc' );
	}

	public function getSubscriptionManager(): SubscriptionManager {
		if ( $this->subscriptionManager === null ) {
			$connectionManager = $this->getRepoConnectionManager();
			$this->subscriptionManager = new SqlSubscriptionManager( $connectionManager );
		}

		return $this->subscriptionManager;
	}

	/**
	 * Returns a factory for connections to the repo wiki's database.
	 */
	private function getRepoConnectionManager(): SessionConsistentConnectionManager {
		return $this->repoDb->sessionConsistentConnections();
	}

	/**
	 * Returns a factory for connections to the client wiki's database.
	 */
	private function getClientConnectionManager(): SessionConsistentConnectionManager {
		return $this->clientDb->sessionConsistentConnections();
	}

	public function getRecentChangesFinder(): RecentChangesFinder {
		return new RecentChangesFinder(
			$this->getClientConnectionManager()
		);
	}

	public function getUsageLookup(): UsageLookup {
		if ( $this->usageLookup === null ) {
			$this->usageLookup = $this->getUsageTracker();
			if ( $this->enableImplicitDescriptionUsage ) {
				$services = MediaWikiServices::getInstance();
				$this->usageLookup = new ImplicitDescriptionUsageLookup(
					$this->usageLookup,
					$services->getTitleFactory(),
					$this->allowLocalShortDesc,
					new DescriptionLookup(
						$this->entityIdLookup,
						$this->termBuffer,
						$services->getPageProps()
					),
					$services->getLinkBatchFactory(),
					$this->siteId,
					$this->getSiteLinkLookup()
				);
			}
		}

		return $this->usageLookup;
	}

	public function getUsageTracker(): SqlUsageTracker {
		if ( $this->usageTracker === null ) {
			$connectionManager = $this->getClientConnectionManager();
			$this->usageTracker = new SqlUsageTracker(
				$this->entityIdParser,
				$connectionManager,
				$this->disabledUsageAspects,
				$this->entityUsagePerPageLimit,
				$this->addEntityUsagesBatchSize
			);
		}

		return $this->usageTracker;
	}

	public function getSiteLinkLookup(): SiteLinkLookup {
		if ( $this->siteLinkLookup === null ) {
			$this->siteLinkLookup = new CachingSiteLinkLookup(
				new SiteLinkTable(
					'wb_items_per_site',
					true,
					$this->repoDb
				),
				new HashBagOStuff()
			);
		}

		return $this->siteLinkLookup;
	}

	/**
	 * The EntityLookup returned by this method will resolve redirects.
	 */
	public function getEntityLookup(): EntityLookup {
		$revisionLookup = $this->getEntityRevisionLookup();
		$revisionBasedLookup = new RevisionBasedEntityLookup( $revisionLookup );
		$resolvingLookup = new RedirectResolvingEntityLookup( $revisionBasedLookup );
		return $resolvingLookup;
	}

	public function getEntityRevisionLookup(): EntityRevisionLookup {
		if ( $this->entityRevisionLookup === null ) {
			$this->entityRevisionLookup = $this->newEntityRevisionLookup();
		}

		return $this->entityRevisionLookup;
	}

	private function newEntityRevisionLookup(): EntityRevisionLookup {
		// NOTE: Keep cache key in sync with SqlStore::newEntityRevisionLookup in WikibaseRepo
		$cacheKeyPrefix = $this->cacheKeyPrefix . ':WikiPageEntityRevisionLookup';

		$dispatchingLookup = $this->wikibaseServices->getEntityRevisionLookup();

		// Lower caching layer using persistent cache (e.g. memcached).
		// TODO: Cleanup the cache, it's not needed as SqlBlobStore itself has a better cache
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			new EntityRevisionCache(
				ObjectCache::getInstance( CACHE_NONE ),
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
	 * @deprecated use WikibaseClient::getEntityLookup instead
	 */
	public function getEntityIdLookup(): EntityIdLookup {
		return $this->entityIdLookup;
	}

	public function getPropertyInfoLookup(): PropertyInfoLookup {
		if ( $this->propertyInfoLookup === null ) {
			$propertyInfoLookup = $this->wikibaseServices->getPropertyInfoLookup();

			$wanCachedPropertyInfoLookup = new CachingPropertyInfoLookup(
				$propertyInfoLookup,
				MediaWikiServices::getInstance()->getMainWANObjectCache(),
				$this->cacheKeyGroup,
				$this->cacheDuration
			);

			$this->propertyInfoLookup = new CachingPropertyInfoLookup(
				$wanCachedPropertyInfoLookup,
				MediaWikiServices::getInstance()->getLocalServerObjectCache(),
				$this->cacheKeyGroup,
				$this->cacheDuration
			);
		}

		return $this->propertyInfoLookup;
	}

	public function getEntityPrefetcher(): EntityPrefetcher {
		return $this->wikibaseServices->getEntityPrefetcher();
	}

	public function getUsageUpdater(): UsageUpdater {
		return new UsageUpdater(
			$this->siteId,
			$this->getUsageTracker(),
			$this->getUsageLookup(),
			$this->getSubscriptionManager()
		);
	}

}
