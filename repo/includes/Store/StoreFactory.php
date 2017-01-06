<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Repo\WikibaseRepo;

/**
 * Factory for obtaining a store instance.
 *
 * @deprecated Use WikibaseRepo::getDefaultInstance()->getStore() instead
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreFactory {

	/**
	 * Returns the default store instance from WikibaseRepo::getDefaultInstance()->getStore().
	 *
	 * @deprecated Use WikibaseRepo::getDefaultInstance()->getStore() instead.
	 *
	 * @param string|bool $storeName Must be false, 'sqlstore', or omitted.
	 * @param string $reset Must be 'no' or omitted.
	 *
	 * @throws InvalidArgumentException
	 * @return Store
	 */
	public static function getStore( $storeName = false, $reset = 'no' ) {
		if ( $storeName !== false && $storeName !== 'sqlstore' ) {
			throw new InvalidArgumentException( 'Unknown store name: ' . $storeName );
		}

		if ( $reset !== 'no' ) {
			throw new InvalidArgumentException( 'Resetting the store instance is no longer supported' );
		}

		return WikibaseRepo::getDefaultInstance()->getStore();
	}

}
