<?php

namespace Wikibase;

interface Store {

	/**
	 * Returns an instance of the store.
	 *
	 * @since 0.1
	 *
	 * @return Store
	 */
	public function singleton();

	/**
	 * Returns a new EntityDeletionHandler for this store.
	 *
	 * @since 0.1
	 *
	 * @return EntityDeletionHandler
	 */
	public function newEntityDeletionHandler();

	/**
	 * Returns a new EntityUpdateHandler for this store.
	 *
	 * @since 0.1
	 *
	 * @return EntityUpdateHandler
	 */
	public function newEntityUpdateHandler();

	/**
	 * Removes all data from the store.
	 *
	 * @since 0.1
	 */
	public function clear();

	// TODO
	public function getIdsForLabel( $language, $label, $description = null );

}
