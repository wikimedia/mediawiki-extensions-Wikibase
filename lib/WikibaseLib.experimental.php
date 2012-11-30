<?php

/**
 * Initialization file for EXPERIMENTAL features of the Wikibase Client extension.
 *
 * This file can be included in LocalSettings.php instead of including WikibaseClient.php,
 * in case all experimental features should be enabled.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'WB_EXPERIMENTAL_FEATURES', 1 );

// include the regular wikibase stuff
require_once( __DIR__ . '/WikibaseLib.php' );

// enable, register and/or configure experimental features here!