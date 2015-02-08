<?php

namespace Wikibase\Test;

use Wikibase\ChangesTable;
use Wikibase\Client\Usage\NullSubscriptionManager;
use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\ClientStore;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\PropertyLabelResolver;
use Wikibase\TermIndex;

/**
 * (Incomplete) ClientStore mock
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
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
	 * @see ClientStore::getUsageLookup
	 *
	 * @return UsageLookup
	 */
	public function getUsageLookup() {
		return new NullUsageTracker();
	}

	/**
	 * @see ClientStore::getUsageTracker
	 *
	 * @return UsageTracker
	 */
	public function getUsageTracker() {
		return new NullUsageTracker();
	}

	/**
	 * @see ClientStore::getSubscriptionManager
	 *
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager() {
		return new NullSubscriptionManager();
	}

	/**
	 * @see ClientStore::getPropertyLabelResolver
	 *
	 * @return PropertyLabelResolver
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
	}

	/**
	 * @see ClientStore::getEntityIdLookup
	 *
	 * @return \Wikibase\Store\EntityIdLookup
	 */
	public function getEntityIdLookup() {
		// FIXME: Incomplete
	}

	/**
	 * @see ClientStore::newChangesTable
	 *
	 * @return ChangesTable
	 */
	public function newChangesTable() {
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
		return $this->getMockRepository();
	}

	/**
	 * @see ClientStore::getEntityRevisionLookup
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getMockRepository();
	}

	/**
	 * @see ClientStore::getSiteLinkLookup
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkLookup() {
		return $this->getMockRepository();
	}

	/**
	 * @see ClientStore::getPropertyInfoStore
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( self::$propertyInfoStore === null ) {
			self::$propertyInfoStore = new MockPropertyInfoStore();
		}

		return self::$propertyInfoStore;
	}

}
