<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

call_user_func( function() {
	global $wgMessagesDirs;
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikibaseView'] = __DIR__ . '/lib/wikibase-data-values-value-view/i18n';
} );
