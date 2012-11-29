<?php

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


// put any additional configuration for debugging here!