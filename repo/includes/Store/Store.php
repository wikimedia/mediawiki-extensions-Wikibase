<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\LegacyEntityTermStoreReader;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\TermIndex;

/**
 * Store interface. All interaction with store Wikibase does on top
 * of storing pages and associated core MediaWiki indexing is done
 * through this interface.
 *
 * @todo provide getXXX() methods for getting local pseudo-singletons (shared service objects).
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Store {

	const LOOKUP_CACHING_ENABLED = '';
	const LOOKUP_CACHING_DISABLED = 'uncached';
	const LOOKUP_CACHING_RETRIEVE_ONLY = 'retrieve-only';

	/**
	 * @return SiteLinkStore
	 */
	public function newSiteLinkStore();

	/**
	 * @return EntityByLinkedTitleLookup
	 */
	public function getEntityByLinkedTitleLookup();

	/**
	 * Removes all data from the store.
	 */
	public function clear();

	/**
	 * Rebuilds the store from the original data source.
	 */
	public function rebuild();

	/**
	 * Use of this method indicates cases that should be migrated away from the expectation
	 * that all of this functionality is provided by a single class. Or that said thing needs
	 * to select one of the more specific services mentioned in the deprecated message.
	 *
	 * @depreacted Use getLegacyEntityTermStoreReader, getLegacyEntityTermStoreWriter
	 * or getLabelConflictFinder directly.
	 *
	 * @return TermIndex
	 */
	public function getTermIndex();

	/**
	 * Use of this method represents cases that still need to be migrated away from
	 * using the legacy terms storage.
	 *
	 * @deprecated This will stop working once Wikibase migrates away from wb_terms
	 * An exact alternative MAY NOT be available.
	 *
	 * @return LegacyEntityTermStoreReader
	 */
	public function getLegacyEntityTermStoreReader();

	/**
	 * This method will result in having 0 calls post migration as the service used
	 * to write to the term store changes in WikibaseRepo::getItemTermStoreWriter
	 * and WikibaseRepo::getPropertyTermStoreWriter
	 *
	 * @deprecated This will stop working once Wikibase migrates away from wb_terms
	 * An alternative will be available
	 *
	 * @return EntityTermStoreWriter
	 */
	public function getLegacyEntityTermStoreWriter();

	/**
	 * @deprecated This will stop working once Wikibase migrates away from wb_terms
	 * An alternative will be available
	 *
	 * @return LabelConflictFinder
	 */
	public function getLabelConflictFinder();

	/**
	 * @return ItemsWithoutSitelinksFinder
	 */
	public function newItemsWithoutSitelinksFinder();

	/**
	 * @return EntityRedirectLookup
	 */
	public function getEntityRedirectLookup();

	/**
	 * @param string $cache One of self::LOOKUP_CACHING_*
	 *        self::LOOKUP_CACHING_DISABLED to get an uncached direct lookup
	 *        self::LOOKUP_CACHING_RETRIEVE_ONLY to get a lookup which reads from the cache, but doesn't store retrieved entities
	 *        self::LOOKUP_CACHING_ENABLED to get a caching lookup (default)
	 *
	 * @param string $lookupMode One of the EntityRevisionLookup lookup mode constants
	 * TODO this should perhaps not refer to EntityRevisionLookup
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup( $cache = self::LOOKUP_CACHING_ENABLED, string $lookupMode = LookupConstants::LATEST_FROM_REPLICA );

	/**
	 * @param string $cache One of self::LOOKUP_CACHING_*
	 *        self::LOOKUP_CACHING_DISABLED to get an uncached direct lookup
	 *        self::LOOKUP_CACHING_RETRIEVE_ONLY to get a lookup which reads from the cache, but doesn't store retrieved entities
	 *        self::LOOKUP_CACHING_ENABLED to get a caching lookup (default)
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $cache = self::LOOKUP_CACHING_ENABLED );

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
