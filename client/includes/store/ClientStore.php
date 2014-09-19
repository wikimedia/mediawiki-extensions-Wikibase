<?php

namespace Wikibase;

use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;

/**
 * Client store interface.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface ClientStore {

	/**
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable();

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
	 * @return EntityLookup
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
	 * @since 0.4
	 *
	 * @return ChangesTable
	 *
	 * @throws \MWException if no changes table can be supplied.
	 */
	public function newChangesTable();

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
}
