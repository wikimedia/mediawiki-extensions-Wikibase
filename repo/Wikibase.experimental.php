<?php

/**
 * Initialization file for EXPERIMENTAL features of the Wikibase extension.
 *
 * This file can be included in LocalSettings.php instead of including Wikibase.php,
 * in case all experimental features should be enabled.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// include the regular wikibase stuff
require_once( __DIR__ . '/Wikibase.php' );

// include the experimental wikibase lib stuff
require_once( __DIR__ . '/../lib/WikibaseLib.experimental.php' );

// enable, register and/or configure experimental features here!