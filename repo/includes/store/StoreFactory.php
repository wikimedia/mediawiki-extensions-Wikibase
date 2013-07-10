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
	 * @param boolean|string $store
	 * @param string         $reset set to 'reset' to force a fresh instance to be returned.
	 *
	 * @return Store
	 */
	public static function getStore( $store = false, $reset = 'no' ) {
		global $wgWBStores;
		static $instances = array();

		$store = $store === false || !array_key_exists( $store, $wgWBStores ) ? Settings::get( 'defaultStore' ) : $store;

		if ( $reset !== true && $reset !== 'reset'
			&& isset( $instances[$store] ) ) {

			return $instances[$store];
		}

		$settings = Settings::singleton();
		$class = $wgWBStores[$store];

		// Since we can't know what settings the Store needs,
		// delegate the instantiation to a factory method (poor man's builder).
		$instance = $class::newFromSettings( $settings );

		assert( $instance instanceof Store );

		$instances[$store] = $instance;
		return $instance;
	}

}
