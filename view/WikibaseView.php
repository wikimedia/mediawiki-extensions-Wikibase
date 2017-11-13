<?php

if ( defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'WIKIBASE_VIEW_VERSION', '0.1-dev' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Load autoload info as long as extension classes are not PSR-4-autoloaded
require_once __DIR__  . '/autoload.php';

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/init.mw.php';
	} );
}
