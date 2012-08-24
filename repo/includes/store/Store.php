<?php

namespace Wikibase;

/**
 * Store interface. All interaction with store Wikibase does on top
 * of storing pages and associated core MediaWiki indexing is done
 * throuhg this interface.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Store {

	/**
	 * Returns an instance of the store.
	 *
	 * @since 0.1
	 *
	 * @return Store
	 */
	public static function singleton();

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

	/**
	 * Rebuilds the store.
	 *
	 * @since 0.1
	 */
	public function rebuild();

	/**
	 * Returns a new TermLookup for this store.
	 *
	 * @since 0.1
	 *
	 * @return TermLookup
	 */
	public function newTermLookup();

}
