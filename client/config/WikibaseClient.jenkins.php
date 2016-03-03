<?php

/**
 * Configuration for the Wikibase Client extension for use with Jenkins CI.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

if ( !defined( 'WBC_VERSION' ) ) {
	die( 'Not an entry point. Load WikibaseClient.php first.' );
}

// Load example settings:
include __DIR__ . '/WikibaseClient.example.php';

// Apply additional settings for Jenkins CI:

// Force the siteGroup name, so we do not rely on looking up the
// group in the sites table during testing.
$wgWBClientSettings['siteGroup'] = "mywikigroup";
