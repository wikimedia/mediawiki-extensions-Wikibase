<?php

define( 'WIKIBASE_SERIALIZATION_JAVASCRIPT_VERSION', '3.0.0' );

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/init.mw.php';
	} );
}
