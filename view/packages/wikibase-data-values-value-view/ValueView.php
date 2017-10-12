<?php

if ( defined( 'VALUEVIEW_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'VALUEVIEW_VERSION', '0.21.0' );

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/ValueView.mw.php';
}
