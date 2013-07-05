<?php

namespace Wikibase;

/**
 * Implementation of the store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlStore implements Store {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable = null;

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

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
			array( 'page_content_model' => EntityContentFactory::singleton()->getEntityContentModels() ),
			__METHOD__,
			array( 'LIMIT' => 1000 ) // TODO: continuation
		);

		foreach ( $pages as $pageRow ) {
			$page = \WikiPage::newFromID( $pageRow->page_id );
			$revision = \Revision::newFromId( $pageRow->page_latest );
			$page->doEditUpdates( $revision, $GLOBALS['wgUser'] );
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

				if ( $type = 'sqlite' ) {
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
	 * @return CachingEntityLoader
	 */
	protected function newEntityLookup() {
		//TODO: get cache type etc from config
		//NOTE: two layers of caching: persistent external cache in WikiPageEntityLookup;
		//      transient local cache in CachingEntityLoader.
		$lookup = new WikiPageEntityLookup( false );
		return new CachingEntityLoader( $lookup );
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

			//TODO: get cache type etc from config
			//TODO: better version ID from config!
			$key = wfWikiID() . '/Wikibase/CachingPropertyInfoStore/' . WBL_VERSION;
			return new CachingPropertyInfoStore( $table, wfGetMainCache(), 3600, $key );
		} else {
			// dummy info store
			return new DummyPropertyInfoStore();
		}
	}

}
