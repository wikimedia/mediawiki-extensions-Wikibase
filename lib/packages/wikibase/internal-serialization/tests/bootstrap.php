<?php

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$autoLoader = require __DIR__ . '/../vendor/autoload.php';

$autoLoader->addPsr4(
	'Tests\\Integration\\Wikibase\\InternalSerialization\\', __DIR__ . '/integration/'
);

unset( $autoLoader );
