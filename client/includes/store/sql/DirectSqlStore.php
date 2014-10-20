<?php

namespace Wikibase;

use HashBagOStuff;
use LoadBalancer;
use ObjectCache;
use Site;
use Wikibase\Client\Store\Sql\ConnectionManager;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\Client\Usage\SiteLinkUsageLookup;
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
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var string|bool The database name of the repo wiki or false for the local wiki
	 */
	private $repoWiki;

	/**
	 * @var string
	 */
	private $cachePrefix;

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
	 * @var Site|null
	 */
	private $site = null;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param string $languageCode
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $repoWiki The database name of the repo wiki or false for the local wiki
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		$languageCode,
		EntityIdParser $entityIdParser,
		$repoWiki
	) {
		$this->contentCodec = $contentCodec;
		$this->languageCode = $languageCode;
		$this->entityIdParser = $entityIdParser;
		$this->repoWiki = $repoWiki;

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$this->cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$this->useLegacyUsageIndex = $settings->getSetting( 'useLegacyUsageIndex' );
	}

	/**
	 * @see Store::getSubscriptionManager
	 *
	 * @since 0.5
	 *
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager() {
		return new SubscriptionManager();
	}

	/**
	 * Returns a LoadBalancer that acts as a factory for connections to the local (client) wiki's
	 * database.
	 *
	 * @return LoadBalancer
	 */
	private function getLocalLoadBalancer() {
		return wfGetLB();
	}

	/**
	 * @see Store::getUsageLookup
	 *
	 * @note: If the useLegacyUsageIndex option is set, this returns a SiteLinkUsageLookup.
	 *
	 * @since 0.5
	 *
	 * @return UsageLookup
	 */
	public function getUsageLookup() {
		if ( $this->usageLookup === null ) {
			if ( $this->useLegacyUsageIndex ) {
				$this->usageLookup = new SiteLinkUsageLookup(
					$this->getSite()->getGlobalId(),
					$this->getSiteLinkTable(),
					new TitleFactory()
				);
			} else {
				$this->usageLookup = $this->getUsageTracker();
			}
		}

		return $this->usageLookup;
	}

	/**
	 * @see Store::getUsageTracker
	 *
	 * @note: If the useLegacyUsageIndex option is set, this returns a NullUsageTracker!
	 *
	 * @since 0.5
	 *
	 * @return UsageTracker
	 */
	public function getUsageTracker() {
		if ( $this->usageTracker === null ) {
			if ( $this->useLegacyUsageIndex ) {
				$this->usageTracker = new NullUsageTracker();
			} else {
				$connectionManager = new ConnectionManager( $this->getLocalLoadBalancer() );
				$this->usageTracker = new SqlUsageTracker( $this->entityIdParser, $connectionManager );
			}
		}

		return $this->usageTracker;
	}

	/**
	 * Sets the site object representing the local wiki.
	 * For testing only!
	 *
	 * @todo: remove this once the Site can be injected via the constructor!
	 *
	 * @param Site $site
	 */
	public function setSite( Site $site ) {
		$this->site = $site;
	}

	/**
	 * Returns the site object representing the local wiki.
	 *
	 * @return Site
	 */
	private function getSite() {
		// @FIXME: inject the site
		if ( $this->site === null ) {
			$this->site = WikibaseClient::getDefaultInstance()->getSite();
		}

		return $this->site;
	}

	/**
	 * @see Store::getSiteLinkTable
	 *
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
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
	 * @since 0.4
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
	 * @since 0.5
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
		// NOTE: Keep cache key in sync with SqlStore::newEntityLookup on the Repo
		$cacheKeyPrefix = $this->cachePrefix . ':WikiPageEntityRevisionLookup';

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
		$hashCachingLookup = new CachingEntityRevisionLookup(
			$persistentCachingLookup,
			new HashBagOStuff()
		);
		// No need to verify the revision ID, we'll ignore updates that happen during the request.
		$hashCachingLookup->setVerifyRevision( false );

		return $hashCachingLookup;
	}

	/**
	 * Get a TermIndex object
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
	 * Get a PropertyLabelResolver object
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver() {
		if ( $this->propertyLabelResolver === null ) {
			// Cache key needs to be language specific
			$cacheKey = $this->cachePrefix . ':TermPropertyLabelResolver' . '/' . $this->languageCode;

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
	 * @see Store::newChangesTable
	 *
	 * @since 0.4
	 *
	 * @return ChangesTable
	 */
	public function newChangesTable() {
		return new ChangesTable( $this->repoWiki );
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.3
	 */
	public function clear() {
		// noop
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.3
	 */
	public function rebuild() {
		$this->clear();
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( $this->propertyInfoTable === null ) {
			$usePropertyInfoTable = WikibaseClient::getDefaultInstance()
				->getSettings()->getSetting( 'usePropertyInfoTable' );

			if ( $usePropertyInfoTable ) {
				$cacheKey = $this->cachePrefix . ':CachingPropertyInfoStore';

				$this->propertyInfoTable = new CachingPropertyInfoStore(
					new PropertyInfoTable( true, $this->repoWiki ),
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
