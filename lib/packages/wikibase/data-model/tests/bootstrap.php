<?php

/**
 * PHPUnit test bootstrap file for the Wikibase DataModel component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

echo exec( 'composer update' ) . "\n";

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

require_once( __DIR__ . '/../vendor/autoload.php' );

require_once( __DIR__ . '/testLoader.php' );