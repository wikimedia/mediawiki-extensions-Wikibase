<?php

/**
 * PHPUnit test bootstrap file for the Wikibase DataModel component.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$autoLoader = require_once( __DIR__ . '/../vendor/autoload.php' );

$autoLoader->addPsr4( 'Wikibase\\Test\\', __DIR__ . '/unit/' );
$autoLoader->addPsr4( 'Wikibase\\Test\\DataModel\\Fixtures\\', __DIR__ . '/fixtures/' );