<?php

namespace Wikibase\Client\Store;

use HashBagOStuff;
use Wikibase\Client\RepositorySpecificServices;
use Wikibase\ClientStore;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\CachingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\SettingsArray;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class DispatchingServiceStore implements ClientStore {

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
	 * TODO: Temporary hack
	 */
	private $fallbackStore;

	/**
	 * @var RepositorySpecificServices
	 */
	private $repositorySpecificServices;

	public function __construct(
		SettingsArray $settings,
		RepositorySpecificServices $repositorySpecificServices,
		ClientStore $fallbackStore
	) {
		$this->repositorySpecificServices = $repositorySpecificServices;
		$this->cacheKeyPrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$this->cacheType = $settings->getSetting( 'sharedCacheType' );
		$this->cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		// TODO: fix me, temporary hack:
		$this->fallbackStore = $fallbackStore;
	}

	/**
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		return new DispatchingEntityLookup( $this->repositorySpecificServices->getEntityLookups() );
		// TODO: or should this rather be like below?
		// return new RedirectResolvingEntityLookup(
		//     new RevisionBasedEntityLookup(
		//		 $this->getEntityRevisionLookup()
		//	 )
		// );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		// NOTE: Keep cache key in sync with SqlStore::newEntityRevisionLookup in WikibaseRepo
		$cacheKeyPrefix = $this->cacheKeyPrefix . ':WikiPageEntityRevisionLookup';

		$dispatchingLookup = new DispatchingEntityRevisionLookup(
			$this->repositorySpecificServices->getEntityRevisionLookups()
		);

		// Lower caching layer using persistent cache (e.g. memcached).
		$persistentCachingLookup = new CachingEntityRevisionLookup(
			$dispatchingLookup,
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

	public function getRecentChangesDuplicateDetector() {
		// TODO: real implementation
		return $this->fallbackStore->getRecentChangesDuplicateDetector();
	}

	public function getSiteLinkLookup() {
		// TODO: real implementation
		return $this->fallbackStore->getSiteLinkLookup();
	}

	public function getUsageLookup() {
		// TODO: real implementation
		return $this->fallbackStore->getUsageLookup();
	}

	public function getUsageTracker() {
		// TODO: real implementation
		return $this->fallbackStore->getUsageTracker();
	}

	public function getSubscriptionManager() {
		// TODO: real implementation
		return $this->fallbackStore->getSubscriptionManager();
	}

	public function getPropertyLabelResolver() {
		// TODO: real implementation
		return $this->fallbackStore->getPropertyLabelResolver();
	}

	public function getTermIndex() {
		// TODO: real implementation
		return $this->fallbackStore->getTermIndex();
	}

	public function getEntityIdLookup() {
		// TODO: real implementation
		return $this->fallbackStore->getEntityIdLookup();
	}

	public function getPropertyInfoStore() {
		// TODO: real implementation
		return $this->fallbackStore->getPropertyInfoStore();
	}

	public function getEntityPrefetcher() {
		// TODO: real implementation
		return $this->fallbackStore->getEntityPrefetcher();
	}

	public function getUsageUpdater() {
		// TODO: real implementation
		return $this->fallbackStore->getUsageUpdater();
	}

	public function getEntityChangeLookup() {
		// TODO: real implementation
		return $this->fallbackStore->getEntityChangeLookup();
	}

	public function clear() {
	}

	public function rebuild() {
	}

}
