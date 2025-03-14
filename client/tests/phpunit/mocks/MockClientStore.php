<?php

namespace Wikibase\Client\Tests\Mocks;

use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\NullSubscriptionManager;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\MockRepository;

/**
 * (Incomplete) ClientStore mock
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class MockClientStore implements ClientStore {

	/**
	 * @var MockRepository|null
	 */
	private static $mockRepository = null;

	/**
	 * @var PropertyInfoLookup|null
	 */
	private static $propertyInfoLookup = null;

	/**
	 * @var EntityLookup|null
	 */
	private static $entityLookup = null;

	/**
	 * @see ClientStore::getUsageLookup
	 *
	 * @return NullUsageTracker
	 */
	public function getUsageLookup() {
		return new NullUsageTracker();
	}

	/**
	 * @see ClientStore::getUsageTracker
	 *
	 * @return NullUsageTracker
	 */
	public function getUsageTracker() {
		return new NullUsageTracker();
	}

	/**
	 * @see ClientStore::getSubscriptionManager
	 *
	 * @return NullSubscriptionManager
	 */
	public function getSubscriptionManager() {
		return new NullSubscriptionManager();
	}

	/**
	 * @deprecated use WikibaseClient::getEntityLookup instead
	 *
	 * @see ClientStore::getEntityIdLookup
	 *
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		// FIXME: Incomplete
	}

	/**
	 * @return MockRepository
	 */
	private function getMockRepository() {
		if ( self::$mockRepository === null ) {
			self::$mockRepository = new MockRepository();
		}

		return self::$mockRepository;
	}

	/**
	 * @see ClientStore::getEntityLookup
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		return self::$entityLookup ?? $this->getMockRepository();
	}

	public function setEntityLookup( ?EntityLookup $entityLookup ) {
		self::$entityLookup = $entityLookup;
	}

	/**
	 * @see ClientStore::getEntityRevisionLookup
	 *
	 * @return MockRepository
	 */
	public function getEntityRevisionLookup() {
		return $this->getMockRepository();
	}

	/**
	 * @return RecentChangesFinder|null
	 */
	public function getRecentChangesFinder() {
		return null;
	}

	/**
	 * @see ClientStore::getSiteLinkLookup
	 *
	 * @return MockRepository
	 */
	public function getSiteLinkLookup() {
		return $this->getMockRepository();
	}

	/**
	 * @see ClientStore::getEntityPrefetcher
	 *
	 * @return NullEntityPrefetcher
	 */
	public function getEntityPrefetcher() {
		return new NullEntityPrefetcher();
	}

	/**
	 * @return UsageUpdater
	 */
	public function getUsageUpdater() {
		return new UsageUpdater(
			'mock',
			$this->getUsageTracker(),
			$this->getUsageLookup(),
			$this->getSubscriptionManager()
		);
	}

}
