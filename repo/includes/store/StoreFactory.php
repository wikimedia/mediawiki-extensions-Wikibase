<?php

namespace Wikibase;

$wbDefaultStore = 'sqlstore'; // TODO: setting

$wbStores = array();
$wbStores['sqlstore'] = '\Wikibase\SQLStore'; // TODO

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
		global $wbDefaultStore, $wbStores;
		$store = $store === false || !array_key_exists( $store, $wbStores ) ? $wbDefaultStore : $store;

		$class = $wbStores[$store];

		return $class::singleton();
	}

}