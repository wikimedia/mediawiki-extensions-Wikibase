<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseSerializationJavaScript', __DIR__ . '/mediawiki-extension.json' );
}

include __DIR__ . '/resources.php';
include __DIR__ . '/resources.tests.php';
