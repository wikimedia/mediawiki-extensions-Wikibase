<?php

namespace Wikibase;

use DBQueryError;
use ObjectCache;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\WikibaseRepo;

/**
 * Implementation of the store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SqlStore implements Store {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup = null;

	/**
	 * @var EntityInfoBuilder
	 */
	private $entityInfoBuilder = null;

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable = null;

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

	/**
	 * @var string
	 */
	private $cachePrefix;

	/**
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	public function __construct() {
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
	 * @see Store::getTermIndex
	 *
	 * @since 0.4
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		if ( !$this->termIndex ) {
			$this->termIndex = $this->newTermIndex();
		}

		return $this->termIndex;
	}

	/**
	 * @since 0.1
	 *
	 * @return TermIndex
	 */
	protected function newTermIndex() {
		//TODO: Get $stringNormalizer from WikibaseRepo?
		//      Can't really pass this via the constructor...
		$stringNormalizer = new StringNormalizer();
		return new TermSqlIndex( $stringNormalizer );
	}

	/**
	 * @see Store::clear
	 *
	 * @since 0.1
	 */
	public function clear() {
		$this->newSiteLinkCache()->clear();
		$this->getTermIndex()->clear();
		$this->newEntityPerPage()->clear();
	}

	/**
	 * @see Store::rebuild
	 *
	 * @since 0.1
	 */
	public function rebuild() {
		$dbw = wfGetDB( DB_MASTER );

		// TODO: refactor selection code out (relevant for other stores)

		$pages = $dbw->select(
			array( 'page' ),
			array( 'page_id', 'page_latest' ),
			array( 'page_content_model' => WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getEntityContentModels() ),
			__METHOD__,
			array( 'LIMIT' => 1000 ) // TODO: continuation
		);

		foreach ( $pages as $pageRow ) {
			$page = \WikiPage::newFromID( $pageRow->page_id );
			$revision = \Revision::newFromId( $pageRow->page_latest );
			try {
				$page->doEditUpdates( $revision, $GLOBALS['wgUser'] );
			} catch ( DBQueryError $e ) {
				wfLogWarning(
					'editUpdateFailed for ' . $page->getId() . ' on revision ' .
					$revision->getId() . ': ' . $e->getMessage()
				);
			}
		}
	}

	/**
	 * Updates the schema of the SQL store to it's latest version.
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseUpdater $updater
	 */
	public function doSchemaUpdate( \DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		$type = $db->getType();

		// TODO the following ifs are utterly confusing and need some clean up
		// i.e. there are code branches that are unreachable, extension adding happens a little
		// on the start, a little later, etc.
		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			// Update from 0.1.
			if ( !$db->tableExists( 'wb_terms' ) ) {
				$updater->dropTable( 'wb_items_per_site' );
				$updater->dropTable( 'wb_items' );
				$updater->dropTable( 'wb_aliases' );
				$updater->dropTable( 'wb_texts_per_lang' );

				$updater->addExtensionTable(
					'wb_terms',
					__DIR__ . '/Wikibase' . $extension
				);

				$this->rebuild();
			}

			// Update from 0.1 or 0.2.
			if ( !$db->fieldExists( 'wb_terms', 'term_search_key' ) &&
				!Settings::get( 'withoutTermSearchKey' ) ) {

				$termsKeyUpdate = 'AddTermsSearchKey' . $extension;

				if ( $type === 'sqlite' ) {
					$termsKeyUpdate = 'AddTermsSearchKey.sqlite.sql';
				}

				$updater->addExtensionField(
					'wb_terms',
					'term_search_key',
					__DIR__ . '/' . $termsKeyUpdate
				);

				$updater->addPostDatabaseUpdateMaintenance( 'Wikibase\RebuildTermsSearchKey' );
			}

			// Update from 0.1. or 0.2.
			if ( !$db->tableExists( 'wb_entity_per_page' ) ) {

				$updater->addExtensionTable(
					'wb_entity_per_page',
					__DIR__ . '/AddEntityPerPage' . $extension
				);

				$updater->addPostDatabaseUpdateMaintenance( 'Wikibase\RebuildEntityPerPage' );
			}

			// Update from 0.1 or 0.2.
			if ( !$db->fieldExists( 'wb_terms', 'term_row_id' ) ) {
				// creates wb_terms.term_row_id
				// and also wb_item_per_site.ips_row_id.

				$alteredExtension = $extension;
				if ( $type === 'sqlite' ) {
					$alteredExtension = '.sqlite' . $alteredExtension;
				}

				$updater->addExtensionField(
					'wb_terms',
					'term_row_id',
					__DIR__ . '/AddRowIDs' . $alteredExtension
				);
			}

			// Update to add weight to wb_terms
			if ( !$db->fieldExists( 'wb_terms' , 'term_weight' ) ) {
				// creates wb_terms.wb_weight

				$alteredExtension = $extension;
				if ( $type === 'sqlite' ) {
					$alteredExtension = '.sqlite' . $alteredExtension;
				}

				$updater->addExtensionField(
					'wb_terms',
					'term_weight',
					__DIR__ . '/AddTermsWeight' . $alteredExtension
				);

			}
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase." );
		}

		PropertyInfoTable::registerDatabaseUpdates( $updater );
	}

	/**
	 * @see Store::newIdGenerator
	 *
	 * @since 0.1
	 *
	 * @return IdGenerator
	 */
	public function newIdGenerator() {
		return new SqlIdGenerator( 'wb_id_counters', wfGetDB( DB_MASTER ) );
	}

	/**
	 * @see Store::newSiteLinkCache
	 *
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 */
	public function newSiteLinkCache() {
		return new SiteLinkTable( 'wb_items_per_site', false );
	}

	/**
	 * @see Store::newEntityPerPage
	 *
	 * @since 0.3
	 *
	 * @return EntityPerPage
	 */
	public function newEntityPerPage() {
		return new EntityPerPageTable();
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
	 * Creates a new EntityLookup
	 *
	 * @return EntityLookup
	 */
	protected function newEntityLookup() {
		//NOTE: two layers of caching: persistent external cache in WikiPageEntityLookup;
		//      transient local cache in CachingEntityLoader.
		//NOTE: Keep in sync with DirectSqlStore::newEntityLookup on the client
		$key = $this->cachePrefix . ':WikiPageEntityLookup';
		$lookup = new WikiPageEntityLookup( false, $this->cacheType, $this->cacheDuration, $key );
		return new CachingEntityLoader( $lookup );
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		if ( !$this->entityRevisionLookup ) {
			$this->entityRevisionLookup = $this->newEntityRevisionLookup();
		}

		return $this->entityRevisionLookup;
	}

	/**
	 * Creates a new EntityRevisionLookup
	 *
	 * @return EntityRevisionLookup
	 */
	protected function newEntityRevisionLookup() {
		//TODO: implement CachingEntityLoader based on EntityRevisionLookup instead of
		//      EntityLookup. Then we can layer an EntityLookup on top of that.
		$key = $this->cachePrefix . ':WikiPageEntityLookup';
		$lookup = new WikiPageEntityLookup( false, $this->cacheType, $this->cacheDuration, $key );
		return $lookup;
	}

	/**
	 * @see Store::getEntityInfoBuilder
	 *
	 * @since 0.4
	 *
	 * @return EntityInfoBuilder
	 */
	public function getEntityInfoBuilder() {
		if ( !$this->entityInfoBuilder ) {
			$this->entityInfoBuilder = $this->newEntityInfoBuilder();
		}

		return $this->entityInfoBuilder;
	}

	/**
	 * Creates a new EntityInfoBuilder
	 *
	 * @return EntityInfoBuilder
	 */
	protected function newEntityInfoBuilder() {
		//TODO: Get $idParser from WikibaseRepo?
		$idParser = new BasicEntityIdParser();
		$builder = new SqlEntityInfoBuilder( $idParser );
		return $builder;
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( !$this->propertyInfoTable ) {
			$this->propertyInfoTable = $this->newPropertyInfoTable();
		}

		return $this->propertyInfoTable;
	}

	/**
	 * Creates a new PropertyInfoTable
	 *
	 * @return PropertyInfoTable
	 */
	protected function newPropertyInfoTable() {
		if ( Settings::get( 'usePropertyInfoTable' ) ) {
			$table = new PropertyInfoTable( false );
			$key = $this->cachePrefix . ':CachingPropertyInfoStore';
			return new CachingPropertyInfoStore( $table, ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration, $key );
		} else {
			// dummy info store
			return new DummyPropertyInfoStore();
		}
	}

}
