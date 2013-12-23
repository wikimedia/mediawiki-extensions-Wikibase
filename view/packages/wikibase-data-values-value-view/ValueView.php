<?php

/**
 * Entry point for the "ValueView" extension.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

if ( defined( 'VALUEVIEW_VERSION' ) ) {
	// Do not initialize more then once.
	return 1;
}

define( 'VALUEVIEW_VERSION', '0.1' );

/**
 * @deprecated
 */
define( 'ValueView_VERSION', VALUEVIEW_VERSION );

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/ValueView.mw.php';
}
