<?php

/**
 * PHPUnit test bootstrap file for the Wikibase DataModel Services component.
 *
 * @licence GNU GPL v2+
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

$autoLoader = require_once __DIR__ . '/../vendor/autoload.php';

$autoLoader->addPsr4( 'Wikibase\\DataModel\\Services\\Tests\\', __DIR__ . '/unit/' );

unset( $autoLoader );
