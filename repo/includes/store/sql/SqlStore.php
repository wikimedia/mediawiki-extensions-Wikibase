<?php

namespace Wikibase;

/**
 * Implementation of the store interface using an SQL backend via MediaWikis
 * storage abstraction layer.
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
	 * Returns a new EntityDeletionHandler for this store.
	 *
	 * @since 0.1
	 *
	 * @return EntityDeletionHandler
	 */
	public function newEntityDeletionHandler() {
		return new EntitySQLDeletion();
	}

	/**
	 * Returns a new EntityUpdateHandler for this store.
	 *
	 * @since 0.1
	 *
	 * @return EntityUpdateHandler
	 */
	public function newEntityUpdateHandler() {
		return new EntitySQLUpdate();
	}

	/**
	 * Returns a new TermLookup for this store.
	 *
	 * @since 0.1
	 *
	 * @return TermLookup
	 */
	public function newTermLookup() {
		return new TermSQLLookup();
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

		$contentModels = array(
			CONTENT_MODEL_WIKIBASE_ITEM,
			CONTENT_MODEL_WIKIBASE_PROPERTY,
			CONTENT_MODEL_WIKIBASE_QUERY
		);

		$pages = $dbw->select(
			array( 'page' ),
			array( 'page_id', 'page_latest' ),
			array( 'page_content_model' => $contentModels ),
			__METHOD__,
			array( 'LIMIT' => 1000 ) // TODO: continuation
		);

		foreach ( $pages as $pageRow ) {
			$page = \WikiPage::newFromID( $pageRow->page_id );
			$revision = \Revision::newFromId( $pageRow->page_latest );
			$page->doEditUpdates( $revision, $GLOBALS['wgUser'] );
		}
	}

	public function doSchemaUpdate( \DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		$type = $db->getType();

		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			$updater->dropTable( 'wb_items' );
			$updater->dropTable( 'wb_aliases' );
			$updater->dropTable( 'wb_texts_per_lang' );

			$updater->addExtensionTable(
				'wb_id_counters',
				__DIR__ . '/Wikibase' . $extension
			);
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase Client." );
		}
	}

}
