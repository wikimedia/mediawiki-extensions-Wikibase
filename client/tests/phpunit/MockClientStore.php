<?php

namespace Wikibase\Test;

use Wikibase\Client\Usage\NullUsageTracker;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\ClientStore;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\PropertyInfoStore;

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
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var PropertyInfoStore|null
	 */
	private $propertyInfoStore = null;

	public function getUsageLookup() {
		return new NullUsageTracker();
	}

	public function getUsageTracker() {
		return new NullUsageTracker();
	}

	public function getSubscriptionManager() {
		return new SubscriptionManager();
	}

	public function getPropertyLabelResolver() {
	}

	public function getTermIndex() {
	}

	public function newChangesTable() {
	}

	public function clear() {
	}

	public function rebuild() {
	}

	private function getMockRepository() {
		if ( $this->mockRepository === null ) {
			$this->mockRepository = new MockRepository();
		}

		return $this->mockRepository;
	}

	/*
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		return $this->getMockRepository();
	}

	/*
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getMockRepository();
	}

	/**
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
		return $this->getMockRepository();
	}

	/**
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( $this->propertyInfoStore === null ) {
			$this->propertyInfoStore = new MockPropertyInfoStore();
		}

		return $this->propertyInfoStore;
	}

}
