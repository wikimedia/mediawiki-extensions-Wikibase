<?php

namespace Wikibase\Test;

use Wikibase\ClientStore;
use Wikibase\PropertyInfoStore;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * (Incomplete) ClientStore mock
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class MockClientStore implements ClientStore {
	public function getItemUsageIndex() {}
	public function getPropertyLabelResolver() {}
	public function getTermIndex() {}
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

	/**
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
		return $this->getMock();
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