<?php

// Set up standard profiler
$wgProfiler['class'] = 'Profiler';

// Only record profiling info for requests that took longer than this
$wgProfileLimit = 0.1; // seconds

// Log sums from profiling into "profiling" table in db
// NOTE: you need to create the necessary tables in the database first,
// using maintenance/archives/patch-profiling.sql in MediaWiki core.
$wgProfileToDatabase = true;

// If true, print a raw call tree instead of per-function report
$wgProfileCallTree = false;

// Should application server host be put into profiling table
$wgProfilePerHost = false;

// Detects non-matching wfProfileIn/wfProfileOut calls
$wgDebugProfiling = false;

// Output debug message on every wfProfileIn/wfProfileOut
$wgDebugFunctionEntry = 0;

// Enable web access to the profiling info in the database.
// Profiling info will be available from profileinfo.php in the MediaWiki
// installation path (where index.php and api.php can also be found).
$wgEnableProfileInfo = true;
