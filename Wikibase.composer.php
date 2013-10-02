<?php

// Component loader for Composer.
// This entry point is not to be used by anything else then Composer.
// It might well be removed at a future point.

// Config:
// wgWikibaseRepoEnable - defaults to true
// wgEnableWikibaseClient - defaults to true

if ( !array_key_exists( 'wgWikibaseRepoEnable', $GLOBALS ) || $GLOBALS['wgWikibaseRepoEnable'] ) {
	require_once __DIR__ . '/repo/Wikibase.php';
}

if ( !array_key_exists( 'wgEnableWikibaseClient', $GLOBALS ) || $GLOBALS['wgEnableWikibaseClient'] ) {
	require_once __DIR__ . '/client/WikibaseClient.php';
}
