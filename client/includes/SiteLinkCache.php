<?php

namespace Wikibase;

/**
 * Represents the site link cache of a single cluster.
 * Corresponds to the wbc_items_per_site table.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkCache extends SiteLinkTable {

	/**
	 * @private
	 */
	public function __construct( $a ) {
		if ( $a !== 'use singleton!' ) {
			throw new \MWException( 'use the singleton method' );
		}

		parent::__construct( 'wbc_items_per_site' );
	}

	/**
	 * @since 0.1
	 *
	 * @return SiteLinkCache
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static( 'use singleton!' );
		}

		return $instance;
	}

}