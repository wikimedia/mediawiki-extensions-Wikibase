<?php

define( 'WIKIBASE_SERIALIZATION_JAVASCRIPT_VERSION', '2.0.2-alpha' );

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/init.mw.php';
	} );
}
