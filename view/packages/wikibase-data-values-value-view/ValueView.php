<?php

if ( defined( 'VALUEVIEW_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'VALUEVIEW_VERSION', '0.18.2' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/ValueView.mw.php';
}
