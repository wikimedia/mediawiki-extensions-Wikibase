<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseView', __DIR__ . '/../extension-view-wip.json' );

$GLOBALS['wgResourceModules'] = array_merge(
	$GLOBALS['wgResourceModules'],
	require __DIR__ . '/lib/resources.php',
	require __DIR__ . '/resources/resources.php'
);
