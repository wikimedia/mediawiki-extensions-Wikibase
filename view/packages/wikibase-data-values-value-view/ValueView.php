<?php

/**
 * Entry point for the "ValueView" extension.
 *
 * Documentation: https://www.mediawiki.org/wiki/Extension:ValueView
 * Support        https://www.mediawiki.org/wiki/Extension_talk:ValueView
 * Source code:   https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/DataValues.git
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

if ( defined( 'VALUEVIEW_VERSION' ) ) {
	// Do not initialize more then once.
	return;
}

define( 'VALUEVIEW_VERSION', '0.1 alpha' );

/**
 * @deprecated
 */
define( 'ValueView_VERSION', VALUEVIEW_VERSION );

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/ValueView.mw.php';
}
