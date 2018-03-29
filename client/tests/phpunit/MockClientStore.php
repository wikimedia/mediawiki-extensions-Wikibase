<?php

namespace Wikibase\Test;

use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\NullSubscriptionManager;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\MockPropertyLabelResolver;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockTermIndex;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * (Incomplete) ClientStore mock
 *
 * @license GPL-2.0-or-later
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
		return new MockTermIndex( [] );
	}

	/**
	 * @see ClientStore::getEntityIdLookup
	 *
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup() {
		// FIXME: Incomplete
	}

	public function getDescriptionLookup() {
		// FIXME: Incomplete
		return new DescriptionLookup( $this->getEntityIdLookup(), $this->getTermIndex() );
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
	 * @see ClientStore::getPropertyInfoLookup
	 *
	 * @return MockPropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		if ( self::$propertyInfoLookup === null ) {
			self::$propertyInfoLookup = new MockPropertyInfoLookup();
		}

		return self::$propertyInfoLookup;
	}

	public function setPropertyInfoLookup( PropertyInfoLookup $propertyInfoLookup ) {
		self::$propertyInfoLookup = $propertyInfoLookup;
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

	public function getEntityChangeLookup() {
		// FIXME: Incomplete
	}

}
