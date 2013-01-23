<?php

/**
 * This file holds registration of experimental features part of the WikibaseClient extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WBC_VERSION' ) ) {
	die( 'Not an entry point.' );
}
$dir = __DIR__ . '/';

// includes/parsers
$wgAutoloadClasses['Wikibase\PropertyParser']       = $dir . 'includes/parserhooks/PropertyParser.php';
