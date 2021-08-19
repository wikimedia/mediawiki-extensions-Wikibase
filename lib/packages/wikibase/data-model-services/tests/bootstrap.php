<?php

/**
 * PHPUnit test bootstrap file for the Wikibase DataModel Services component.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', 1 );

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$classLoader = require __DIR__ . '/../vendor/autoload.php';

$classLoader->addPsr4( 'Wikibase\\DataModel\\Services\\Tests\\', __DIR__ . '/unit/' );
$classLoader->addPsr4( 'Wikibase\\DataModel\\Services\\Fixtures\\', __DIR__ . '/fixtures/' );

unset( $classLoader );
