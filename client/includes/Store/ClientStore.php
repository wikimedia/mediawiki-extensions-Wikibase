<?php

namespace Wikibase\Client\Store;

use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Client store interface.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface ClientStore {

	/**
	 * @return RecentChangesFinder|null
	 */
	public function getRecentChangesFinder();

	/**
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkLookup();

	/**
	 * @return UsageLookup
	 */
	public function getUsageLookup();

	/**
	 * @return UsageTracker
	 */
	public function getUsageTracker();

	/**
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager();

	/**
	 * @return EntityLookup
	 */
	public function getEntityLookup();

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @deprecated use WikibaseClient::getEntityLookup instead
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup();

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

	/**
	 * Returns an EntityPrefetcher which can be used to prefetch a list of entity
	 * ids in case we need to for example load a batch of entity ids.
	 *
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * @return UsageUpdater
	 */
	public function getUsageUpdater();

}
