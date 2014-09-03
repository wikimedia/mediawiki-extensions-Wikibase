<?php

call_user_func( function() {
	global $wgHooks;

	$wgHooks['LoadExtensionSchemaUpdates'][] = 'Wikibase\Usage\Sql\SqlUsageTrackerSchemaUpdater::onSchemaUpdate';
} );
