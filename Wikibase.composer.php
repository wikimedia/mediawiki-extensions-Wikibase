<?php

// Component loader for Composer.
// This entry point is not to be used by anything else then Composer.
// It might well be removed at a future point.

// Config:
// wgEnableWikibaseRepo - defaults to true
// wgEnableWikibaseClient - defaults to true

if ( !array_key_exists( 'wgEnableWikibaseRepo', $GLOBALS ) || $GLOBALS['wgEnableWikibaseRepo'] ) {
	require_once __DIR__ . '/repo/Wikibase.php';
}

if ( !array_key_exists( 'wgEnableWikibaseClient', $GLOBALS ) || $GLOBALS['wgEnableWikibaseClient'] ) {
	require_once __DIR__ . '/client/WikibaseClient.php';
}

if (
	!array_key_exists( 'wgEnableWikibaseClient', $GLOBALS ) || $GLOBALS['wgEnableWikibaseClient'] ||
	!array_key_exists( 'wgEnableWikibaseRepo', $GLOBALS ) || $GLOBALS['wgEnableWikibaseRepo']
) {
	require_once __DIR__ . '/view/WikibaseView.php';
}
