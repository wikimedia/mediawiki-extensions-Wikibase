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
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;

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

	public const LOOKUP_CACHING_ENABLED = '';
	public const LOOKUP_CACHING_DISABLED = 'uncached';
	public const LOOKUP_CACHING_RETRIEVE_ONLY = 'retrieve-only';

	/**
	 * @return SiteLinkStore
	 */
	public function newSiteLinkStore();

	/**
	 * @return EntityByLinkedTitleLookup
	 */
	public function getEntityByLinkedTitleLookup();

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
	 * @param string $lookupMode One of LookupConstants::LATEST_FROM_*
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
