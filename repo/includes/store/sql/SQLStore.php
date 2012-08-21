<?php

namespace Wikibase;

class SQLStore implements Store {

	/**
	 * @see Store::singleton
	 *
	 * @since 0.1
	 *
	 * @return Store
	 */
	public function singleton() {
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
	 * @see Store::clear
	 *
	 * @since 0.1
	 */
	public function clear() {
		$dbw = wfGetDB( DB_MASTER );

		$tables = array(
			'wb_items',
			'wb_items_per_site',
			'wb_texts_per_lang',
			'wb_aliases',
		);

		foreach ( $tables as $table ) {
			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );
		}
	}

	/**
	 * Get the ids of the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the id of the item with
	 * that description will be returned (as only element in the array).
	 *
	 * TODO: refactor to work for all entities
	 *
	 * @since 0.1
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public function getIdsForLabel( $language, $label, $description = null ) {
		$dbr = wfGetDB( DB_SLAVE );

		$conds = array(
			'tpl_language' => $language,
			'tpl_label' => $label
		);

		if ( !is_null( $description ) ) {
			$conds['tpl_description'] = $description;
		}

		$items = $dbr->select(
			'wb_texts_per_lang',
			array( 'tpl_item_id' ),
			$conds,
			__METHOD__
		);

		return array_map( function( $item ) { return $item->tpl_item_id; }, iterator_to_array( $items ) );
	}

}
