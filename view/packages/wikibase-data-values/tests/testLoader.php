<?php

/**
 * Test class autoloader for the DataValues library.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

spl_autoload_register( function ( $className ) {
	$className = ltrim( $className, '\\' );
	$fileName = '';
	$namespace = '';

	if ( $lastNsPos = strripos( $className, '\\') ) {
		$namespace = substr( $className, 0, $lastNsPos );
		$className = substr( $className, $lastNsPos + 1 );
		$fileName  = str_replace( '\\', '/', $namespace ) . '/';
	}

	$fileName .= str_replace( '_', '/', $className ) . '.php';

	$namespaceSegments = explode( '\\', $namespace );

	if ( count( $namespaceSegments ) > 1 && $namespaceSegments[0] === 'DataValues' && $namespaceSegments[1] === 'Tests' ) {
		$fileName = substr( $fileName, 17 );
		require_once __DIR__ . '/phpunit/' . $fileName;
	}
} );
