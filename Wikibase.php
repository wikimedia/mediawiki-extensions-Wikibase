<?php

/**
 * Testing entry point. Do not use for production setups!
 *
 * @see README.md
 * @see https://wikiba.se
 *
 * @license GPL-2.0-or-later
 */

if ( $wgEnableWikibaseRepo ?? true ) {
	wfLoadExtension( 'WikibaseRepository', __DIR__ . '/extension-repo.json' );

	if ( defined( 'MW_QUIBBLE_CI' ) ) {
		require_once __DIR__ . '/repo/config/Wikibase.ci.php';
	}
}

if ( $wgEnableWikibaseClient ?? true ) {
	wfLoadExtension( 'WikibaseClient', __DIR__ . '/extension-client.json' );

	if ( defined( 'MW_QUIBBLE_CI' ) ) {
		require_once __DIR__ . '/client/config/WikibaseClient.ci.php';
	}
}
