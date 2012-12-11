<?php

namespace Wikibase;

/**
 * Factory for obtaining a store instance.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreFactory {

	/**
	 * Returns an instance of the default store, or an alternate store
	 * if so specified with the $store argument.
	 *
	 * @since 0.1
	 *
	 * @param boolean $store
	 *
	 * @return Store
	 */
	public static function getStore( $store = false ) {
		global $wgWBStores;
		$store = $store === false || !array_key_exists( $store, $wgWBStores ) ? Settings::get( 'defaultStore' ) : $store;

		$class = $wgWBStores[$store];

		return new $class;
	}

}
