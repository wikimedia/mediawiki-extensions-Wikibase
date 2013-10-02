<?php

// Component loader for Composer.
// This entry point is not to be used by anything else then Composer.
// It might well be removed at a future point.

// Config:
// egWikibaseRepoEnable - defaults to true
// egEnableWikibaseClient - defaults to true

if ( !array_key_exists( 'egWikibaseRepoEnable', $GLOBALS ) || $GLOBALS['egWikibaseRepoEnable'] ) {
	require_once __DIR__ . '/repo/Wikibase.php';
}

if ( !array_key_exists( 'egEnableWikibaseClient', $GLOBALS ) || $GLOBALS['egEnableWikibaseClient'] ) {
	require_once __DIR__ . '/client/WikibaseClient.php';
}
