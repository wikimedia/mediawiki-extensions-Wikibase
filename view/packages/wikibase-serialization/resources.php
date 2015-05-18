<?php

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	global $wgResourceModules;

	$wgResourceModules = array_merge(
		$wgResourceModules,
		include( __DIR__ . '/src/resources.php' )
	);
} );
