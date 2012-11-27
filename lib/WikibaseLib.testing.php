<?php

/**
 * Initialization file for TESTING settings for the Wikibase Lib.
 *
 * This file can be included in LocalSettings.php instead of including WikibaseLib.php,
 * to set up WikibaseLib for testing.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// include the experimental wikibase stuff
require_once( __DIR__ . '/WikibaseLib.experimental.php' );

// common settings for testing / debugging
error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', 1 );

$wgMessageCacheType = CACHE_NONE;

$wgShowExceptionDetails = true;
$wgShowSQLErrors        = true;
$wgDebugDumpSql         = true;
$wgShowDBErrorBacktrace = true;
$wgDevelopmentWarnings  = true;
$wgDebugTimestamps      = true;
$wgDebugToolbar = true;


// put any additional configuration for testing here!