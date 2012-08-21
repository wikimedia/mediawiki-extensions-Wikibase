<?php

namespace Wikibase;

/**
 * Factory for obtaining a store instance.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
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
		global $wbStores;
		$store = $store === false || !array_key_exists( $store, $wbStores ) ? Settings::get( 'defaultStore' ) : $store;

		$class = $wbStores[$store];

		return $class::singleton();
	}

}
