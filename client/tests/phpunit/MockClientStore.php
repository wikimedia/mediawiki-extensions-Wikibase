<?php

namespace Wikibase\Test;

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
	public function getUsageLookup() {}
	public function getUsageTracker() {}
	public function getSubscriptionManager() {}
	public function getItemUsageIndex() {}
	public function getPropertyLabelResolver() {}
	public function newChangesTable() {}
	public function clear() {}
	public function rebuild() {}

	private function getMock() {
		static $mockRepo = false;
		if ( !$mockRepo ) {
			$mockRepo = new MockRepository();
		}

		return $mockRepo;
	}

	/*
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		return $this->getMock();
	}

	/*
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getMock();
	}

	/**
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
		return $this->getMock();
	}

	/**
	 * @return TermIndex
	 */
	public function getTermIndex() {
		return new MockTermIndex( array() );
	}

	/**
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		static $mockPropertyInfoStore = false;
		if ( !$mockPropertyInfoStore ) {
			$mockPropertyInfoStore = new MockPropertyInfoStore();
		}
		return $mockPropertyInfoStore;
	}

}
