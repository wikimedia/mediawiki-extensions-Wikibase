<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

if ( defined( 'WBUT_VERSION' ) ) {
	// Do not initialize more than once.
	return;
}

define( 'WBUT_VERSION', '0.5 alpha'
	. ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ? '/experimental' : '' ) );

define( 'WBUT_DIR', __DIR__ );
