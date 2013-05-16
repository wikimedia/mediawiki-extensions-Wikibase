<?php

/**
 * Test class autoloader for the Wikibase Database component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDatabase
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

	$inTestNamespace = count( $namespaceSegments ) > 2
		&& $namespaceSegments[0] === 'Wikibase'
		&& $namespaceSegments[1] === 'Database'
		&& $namespaceSegments[2] === 'Tests';

	if ( $inTestNamespace ) {
		$pathParts = explode( '/', $fileName );
		array_shift( $pathParts );
		array_shift( $pathParts );
		array_shift( $pathParts );
		$fileName = implode( '/', $pathParts );

		require_once __DIR__ . '/phpunit/' . $fileName;
	}
} );
