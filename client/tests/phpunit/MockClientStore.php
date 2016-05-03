<?php

namespace Wikibase\Test;

use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\NullSubscriptionManager;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\ClientStore;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Tests\MockPropertyLabelResolver;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Lib\Tests\Store\MockPropertyInfoStore;
use Wikibase\Lib\Tests\Store\MockTermIndex;
use Wikibase\PropertyInfoStore;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * (Incomplete) ClientStore mock
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class MockClientStore implements ClientStore {

	/**
	 * @var string|null
	 */
	private $languageCode;

	/**
	 * @param string|null $languageCode
	 */
	public function __construct( $languageCode = null ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @var MockRepository|null
	 */
	private static $mockRepository = null;

	/**
	 * @var PropertyInfoStore|null
	 */
	private static $propertyInfoStore = null;

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
	 * @see ClientStore::getPropertyLabelResolver
	 *
	 * @return MockPropertyLabelResolver
	 */
	public function getPropertyLabelResolver() {
		return new MockPropertyLabelResolver(
			$this->languageCode ?: 'en',
			$this->getMockRepository()
		);
	}

	/**
	 * @see ClientStore::getTermIndex
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		// FIXME: Incomplete
		return new MockTermIndex( array() );
	}

	/**
	 * @see ClientStore::getEntityIdLookup
	 *
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		// FIXME: Incomplete
	}

	/**
	 * @see ClientStore::clear
	 */
	public function clear() {
	}

	/**
	 * @see ClientStore::rebuild
	 */
	public function rebuild() {
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
		if ( !self::$entityLookup ) {
			return $this->getMockRepository();
		}

		return self::$entityLookup;
	}

	/**
	 * @param EntityLookup|null $entityLookup
	 */
	public function setEntityLookup( EntityLookup $entityLookup = null ) {
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
	 * @since 0.5
	 *
	 * @return RecentChangesDuplicateDetector|null
	 */
	public function getRecentChangesDuplicateDetector() {
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
	 * @see ClientStore::getPropertyInfoStore
	 *
	 * @return MockPropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( self::$propertyInfoStore === null ) {
			self::$propertyInfoStore = new MockPropertyInfoStore();
		}

		return self::$propertyInfoStore;
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
	 * @since 0.5
	 *
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

	public function getEntityChangeLookup() {
		// FIXME: Incomplete
	}

}
