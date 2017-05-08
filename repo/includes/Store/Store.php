<?php

namespace Wikibase;

use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Repo\Store\ChangeStore;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * Store interface. All interaction with store Wikibase does on top
 * of storing pages and associated core MediaWiki indexing is done
 * through this interface.
 *
 * @todo: provide getXXX() methods for getting local pseudo-singletons (shared service objects).
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Store {

	/**
	 * @return SiteLinkStore
	 */
	public function newSiteLinkStore();

	/**
	 * Removes all data from the store.
	 */
	public function clear();

	/**
	 * Rebuilds the store from the original data source.
	 */
	public function rebuild();

	/**
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * @return LabelConflictFinder
	 */
	public function getLabelConflictFinder();

	/**
	 * @return IdGenerator
	 */
	public function newIdGenerator();

	/**
	 * @return EntitiesWithoutTermFinder
	 */
	public function newEntitiesWithoutTermFinder();

	/**
	 * @return ItemsWithoutSitelinksFinder
	 */
	public function newItemsWithoutSitelinksFinder();

	/**
	 * @return EntityRedirectLookup
	 */
	public function getEntityRedirectLookup();

	/**
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $uncached = '' );

	/**
	 * @param string $uncached Flag string, set to 'uncached' to get an uncached direct lookup service.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $uncached = '' );

	/**
	 * @return EntityStore
	 */
	public function getEntityStore();

	/**
	 * Returns an EntityStoreWatcher that should be notified of changes to
	 * entities, in order to keep any caches updated.
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory();

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

	/**
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore();

	/**
	 * @return SiteLinkConflictLookup
	 */
	public function getSiteLinkConflictLookup();

	/**
	 * Returns an EntityPrefetcher which can be used to prefetch a list of entity
	 * ids in case we need to for example load a batch of entity ids.
	 *
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * @return EntityChangeLookup
	 */
	public function getEntityChangeLookup();

	/**
	 * @return ChangeStore
	 */
	public function getChangeStore();

}
