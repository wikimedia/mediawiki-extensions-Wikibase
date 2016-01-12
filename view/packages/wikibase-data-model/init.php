<?php

define( 'WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION', '2.0.0' );

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/init.mw.php';
	} );
}
