<?php

if ( defined( 'WBUT_VERSION' ) ) {
	// Do not initialize more than once.
	return;
}

require_once( __DIR__ . '/UsageTracking.php' );

call_user_func( function() {
	global $wgHooks;

	$wgHooks['LoadExtensionSchemaUpdates'][] = '\Wikibase\Usage\Sql\SqlUsageTrackerSchemaUpdater::onSchemaUpdate';
} );
