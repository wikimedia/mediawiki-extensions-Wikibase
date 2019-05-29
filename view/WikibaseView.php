<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

wfLoadExtension( 'WikibaseView', __DIR__ . '/extension-view-wip.json' );
