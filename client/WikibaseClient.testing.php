<?php

/**
 * Initialization file for TESTING settings for the Wikibase Client extension.
 *
 * This file can be included in LocalSettings.php instead of including WikibaseClient.php,
 * to set up WikibaseClient for testing.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// include the experimental wikibase stuff
require_once( __DIR__ . '/WikibaseClient.experimental.php' );

// include the testing wikibase lib stuff
require_once( __DIR__ . '/../lib/WikibaseLib.testing.php' );


// put any configuration for testing here!