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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlStore implements Store {

	/**
	 * @see Store::singleton
	 *
	 * @since 0.1
	 *
	 * @return Store
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @see Store::newTermCache
	 *
	 * @since 0.1
	 *
	 * @return TermCache
	 */
	public function newTermCache() {
		return new TermSqlCache( 'wb_terms' );
	}

	/**
	 * @see Store::clear
	 *
	 * @since 0.1
	 */
	public function clear() {
		$dbw = wfGetDB( DB_MASTER );

		$tables = array(
			'wb_items_per_site',
			'wb_terms',
		);

		foreach ( $tables as $table ) {
			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );
		}
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
			array( 'page_content_model' => Utils::getEntityModels() ),
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
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase Client." );
		}
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
	 * @return SiteLinkLookup
	 */
	public function newSiteLinkCache() {
		return new SiteLinkTable( 'wb_items_per_site' );
	}

}
