<?php

namespace Wikibase;

use HashBagOStuff;
use ObjectCache;
use Wikibase\Client\Store\Sql\ConnectionManager;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\NullSubscriptionManager;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\Client\Usage\SiteLinkUsageLookup;
use Wikibase\Client\Usage\Sql\SqlSubscriptionManager;
use Wikibase\Client\Usage\Sql\SqlUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Lib\Store\WikiPageEntityRevisionLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactoryMulti.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DirectSqlStore implements ClientStore {

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string|bool The symbolic database name of the repo wiki or false for the local wiki.
	 */
	private $repoWiki;

	/**
	 * @var ConnectionManager|null
	 */
	private $repoConnectionManager = null;

	/**
	 * @var ConnectionManager|null
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
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var bool
	 */
	private $useLegacyUsageIndex;

	/**
	 * @var bool
	 */
	private $useLegacyChangesSubscription;

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
	 * @var SiteLinkTable|null
	 */
	private $siteLinkTable = null;

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
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $repoWiki The symbolic database name of the repo wiki or false for the
	 * local wiki.
	 * @param string $languageCode
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityIdParser $entityIdParser,
		$repoWiki = false,
		$languageCode
	) {
		$this->contentCodec = $contentCodec;
		$this->entityIdParser = $entityIdParser;
		$this->repoWiki = $repoWiki;
		$this->languageCode = $languageCode;

		// @TODO: Inject
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->useLegacyUsageIndex = $settings->getSetting( 'useLegacyUsageIndex' );
		$this->useLegacyChangesSubscription = $settings->getSetting( 'useLegacyChangesSubscription' );
		$this->siteId = $settings->getSetting( 'siteGlobalID' );
	}

	/**
	 * @see ClientStore::getSubscriptionManager
	 *
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager() {
		if ( $this->subscriptionManager === null ) {
			if ( $this->useLegacyChangesSubscription ) {
				$this->subscriptionManager = new NullSubscriptionManager();
			} else {
				$connectionManager = $this->getRepoConnectionManager();
				$this->subscriptionManager = new SqlSubscriptionManager( $connectionManager );
			}
		}

		return $this->subscriptionManager;
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the repo wiki's
	 * database.
	 *
	 * @return ConnectionManager
	 */
	private function getRepoConnectionManager() {
		if ( $this->repoConnectionManager === null ) {
			$this->repoConnectionManager = new ConnectionManager( wfGetLB( $this->repoWiki ), $this->repoWiki );
		}

		return $this->repoConnectionManager;
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the local (client) wiki's
	 * database.
	 *
	 * @return ConnectionManager
	 */
	private function getLocalConnectionManager() {
		if ( $this->localConnectionManager === null ) {
			$this->localConnectionManager = new ConnectionManager( wfGetLB() );
		}

		return $this->localConnectionManager;
	}

	/**
	 * @see ClientStore::getUsageLookup
	 *
	 * @note: If the useLegacyUsageIndex option is set, this returns a SiteLinkUsageLookup.
	 *
	 * @return UsageLookup
	 */
	public function getUsageLookup() {
		if ( $this->usageLookup === null ) {
			if ( $this->useLegacyUsageIndex ) {
				$this->usageLookup = new SiteLinkUsageLookup(
					$this->siteId,
					$this->getSiteLinkLookup(),
					new TitleFactory()
				);
			} else {
				$this->usageLookup = $this->getUsageTracker();
			}
		}

		return $this->usageLookup;
	}

	/**
	 * @see ClientStore::getUsageTracker
	 *
	 * @note: If the useLegacyUsageIndex option is set, this returns a NullUsageTracker!
	 *
	 * @return UsageTracker
	 */
	public function getUsageTracker() {
		if ( $this->usageTracker === null ) {
			if ( $this->useLegacyUsageIndex ) {
				$this->usageTracker = new NullUsageTracker();
			} else {
				$connectionManager = $this->getLocalConnectionManager();
				$this->usageTracker = new SqlUsageTracker( $this->entityIdParser, $connectionManager );
			}
		}

		return $this->usageTracker;
	}

	/**
	 * @see ClientStore::getSiteLinkLookup
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkLookup() {
		if ( $this->siteLinkTable === null ) {
			$this->siteLinkTable = new SiteLinkTable( 'wb_items_per_site', true, $this->repoWiki );
		}

		return $this->siteLinkTable;
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

		$rawLookup = new WikiPageEntityRevisionLookup(
			$this->contentCodec,
			$this->entityIdParser,
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
		$hashCachingLookup = new CachingEntityRevisionLookup( $persistentCachingLookup, new HashBagOStuff() );
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
	 * @see ClientStore::newChangesTable
	 *
	 * @return ChangesTable
	 */
	public function newChangesTable() {
		return new ChangesTable( $this->repoWiki );
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
			$usePropertyInfoTable = WikibaseClient::getDefaultInstance()
				->getSettings()->getSetting( 'usePropertyInfoTable' );

			if ( $usePropertyInfoTable ) {
				$propertyInfoStore = new PropertyInfoTable( true, $this->repoWiki );
				$cacheKey = $this->cacheKeyPrefix . ':CachingPropertyInfoStore';

				$this->propertyInfoTable = new CachingPropertyInfoStore(
					$propertyInfoStore,
					ObjectCache::getInstance( $this->cacheType ),
					$this->cacheDuration,
					$cacheKey
				);
			} else {
				$this->propertyInfoTable = new DummyPropertyInfoStore();
			}
		}

		return $this->propertyInfoTable;
	}

}
