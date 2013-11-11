<?php

namespace Wikibase;

use Language;
use LogicException;
use ObjectCache;

/**
 * Implementation of the client store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: rename to MirrorSqlStore
 */
class CachingSqlStore implements ClientStore {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver = null;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var string
	 */
	private $cachePrefix;

	/**
	 * @var string
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @param Language $wikiLanguage
	 */
	public function __construct( Language $wikiLanguage ) {
		$this->language = $wikiLanguage;

		//NOTE: once I59e8423c is in, we no longer need the singleton.
		$settings = Settings::singleton();
		$cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );

		$this->cachePrefix = $cachePrefix;
		$this->cacheDuration = $cacheDuration;
		$this->cacheType = $cacheType;
	}

	/**
	 * @var SiteLinkTable
	 */
	private $siteLinkTable = null;

	/**
	 * @var ItemUsageIndex
	 */
	private $entityUsageIndex = null;

	/**
	 * @see Store::getItemUsageIndex
	 *
	 * @since 0.4
	 *
	 * @return ItemUsageIndex
	 */
	public function getItemUsageIndex() {
		if ( !$this->entityUsageIndex ) {
			$this->entityUsageIndex = $this->newItemUsageIndex();
		}

		return $this->siteLinkTable;
	}

	/**
	 * @since 0.4
	 *
	 * @return ItemUsageIndex
	 */
	protected function newItemUsageIndex() {
		return new ItemUsageIndex( $this->getSite(), $this->getSiteLinkTable() );
	}

	/**
	 * @todo ClientStoreFactory should be factored into WikibaseClient, so WikibaseClient
	 *       can inject info like the wiki's Site object into the ClientStore instance.
	 *
	 * @return null|\Site
	 */
	private function getSite() {
		$site = \Sites::singleton()->getSite( Settings::get( 'siteGlobalID' ) );
		return $site;
	}

	/**
	 * @see Store::getSiteLinkTable
	 *
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
		if ( !$this->siteLinkTable ) {
			$this->siteLinkTable = $this->newSiteLinkTable();
		}

		return $this->siteLinkTable;
	}

	/**
	 * @since 0.3
	 *
	 * @return SiteLinkLookup
	 */
	protected function newSiteLinkTable() {
		return new SiteLinkTable( 'wbc_items_per_site', true );
	}

	/**
	 * Returns a new EntityCache instance
	 *
	 * @since 0.3
	 *
	 * @return EntityCache
	 *
	 * @todo: rename to newEntityMirror
	 */
	public function newEntityCache() {
		return new EntityCacheTable();
	}

	/**
	 * @see Store::getEntityLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		if ( !$this->entityLookup ) {
			$this->entityLookup = $this->newEntityLookup();
		}

		return $this->entityLookup;
	}

	/**
	 * Create a new EntityLookup
	 *
	 * @return CachingEntityLoader
	 */
	protected function newEntityLookup() {
		$mirror = $this->newEntityCache();
		return new CachingEntityLoader( $mirror );
	}

	/**
	 * Get a PropertyLabelResolver object
	 *
	 * @since 0.4
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver() {
		if ( !$this->propertyLabelResolver ) {
			$this->propertyLabelResolver = $this->newPropertyLabelResolver();
		}

		return $this->propertyLabelResolver;
	}

	/**
	 * Get a TermIndex object
	 *
	 * @throws \LogicException
	 * @return TermIndex
	 */
	public function getTermIndex() {
		throw new LogicException( "Not Implemented, " . __CLASS__ . " is incomplete." );
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @throws \LogicException
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		throw new LogicException( "Not Implemented, " . __CLASS__ . " is incomplete." );
	}

	/**
	 * Create a new PropertyLabelResolver instance
	 *
	 * @return PropertyLabelResolver
	 */
	protected function newPropertyLabelResolver() {
		$key = $this->cachePrefix . ':TermPropertyLabelResolver';
		return new TermPropertyLabelResolver(
			$this->language->getCode(),
			$this->getTermIndex(),
			ObjectCache::getInstance( $this->cacheType ),
			$this->cacheDuration,
			$key
		);
	}

	/**
	 * Throws an MWException, because no changes table is available.
	 *
	 * @since 0.4
	 *
	 * @throws \MWException because no changes table can be supplied.
	 */
	public function newChangesTable() {
		throw new \MWException( "no changes table available" );
	}

	/**
	 * Delete client store data
	 *
	 * @since 0.2
	 */
	public function clear() {
		$this->newEntityCache()->clear();

		$tables = array(
			'wbc_item_usage',
			'wbc_query_usage',
		);

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $tables as $table ) {
			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );
		}
	}

	/**
	 * Rebuild client store data
	 *
	 * @since 0.2
	 */
	public function rebuild() {
		$this->clear();
	}

}
