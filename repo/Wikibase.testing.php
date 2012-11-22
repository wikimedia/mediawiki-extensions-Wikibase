<?php

/**
 * Initialization file for TESTING settings for the Wikibase extension.
 *
 * This file can be included in LocalSettings.php instead of including Wikibase.php,
 * to set up Wikibase for testing.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// include the experimental wikibase stuff
require_once( __DIR__ . '/Wikibase.experimental.php' );

// include the testing wikibase lib stuff
require_once( __DIR__ . '/../lib/WikibaseLib.testing.php' );


// put any configuration for testing here!