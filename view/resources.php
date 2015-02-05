<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
return call_user_func( function() {
	global $wgResourceModules;

	$wgResourceModules = array_merge(
		$wgResourceModules,
		include __DIR__ . '/resources/resources.php'
	);
} );
