<?php

namespace Wikibase;

use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * Client store interface.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface ClientStore {

	/**
	 * @since 0.5
	 *
	 * @return RecentChangesDuplicateDetector|null
	 */
	public function getRecentChangesDuplicateDetector();

	/**
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkLookup();

	/**
	 * @since 0.5
	 *
	 * @return UsageLookup
	 */
	public function getUsageLookup();

	/**
	 * @since 0.5
	 *
	 * @return UsageTracker
	 */
	public function getUsageTracker();

	/**
	 * @since 0.5
	 *
	 * @return SubscriptionManager
	 */
	public function getSubscriptionManager();

	/**
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup();

	/**
	 * @since 0.5
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @since 0.4
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver();

	/**
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * @since 0.5
	 *
	 * @return EntityIdLookup
	 */
	public function getEntityIdLookup();

	/**
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.2
	 */
	public function clear();

	/**
	 * Rebuilds all data in the store.
	 *
	 * @since 0.2
	 */
	public function rebuild();

	/**
	 * Returns an EntityPrefetcher which can be used to prefetch a list of entity
	 * ids in case we need to for example load a batch of entity ids.
	 *
	 * @since 0.5
	 *
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * @since 0.5
	 *
	 * @return UsageUpdater
	 */
	public function getUsageUpdater();

	/**
	 * @since 0.5
	 *
	 * @return EntityChangeLookup
	 */
	public function getEntityChangeLookup();

}
