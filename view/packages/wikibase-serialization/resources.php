<?php
/**
 * @codeCoverageIgnoreStart
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

return call_user_func( function() {
	global $wgResourceModules;

	$wgResourceModules = array_merge(
		$wgResourceModules,
		include( __DIR__ . '/src/resources.php' )
	);
} );
