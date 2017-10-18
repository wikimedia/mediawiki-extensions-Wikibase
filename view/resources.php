<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	global $wgResourceModules;

	$wgResourceModules = array_merge(
		$wgResourceModules,
		include __DIR__ . '/lib/resources.php',
		include __DIR__ . '/resources/resources.php'
	);
} );
