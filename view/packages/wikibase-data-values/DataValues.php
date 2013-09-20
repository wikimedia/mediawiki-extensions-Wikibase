<?php

/**
 * Entry point for the DataValues extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:DataValues
 * Support					https://www.mediawiki.org/wiki/Extension_talk:DataValues
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/DataValues.git
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * Files belonging to the DataValues extension.
 *
 * @defgroup DataValues DataValues
 */

/**
 * Tests part of the DataValues extension.
 *
 * @defgroup DataValuesTests DataValuesTests
 * @ingroup DataValues
 */

if ( defined( 'DataValues_VERSION' ) ) {
	// Do not initialize more then once.
	return;
}

define( 'DATAVALUES_VERSION', '0.1 alpha' );

/**
 * @deprecated
 */
define( 'DataValues_VERSION', DATAVALUES_VERSION );

spl_autoload_register( function ( $className ) {
	if ( in_array( $className, array( 'Comparable', 'Copyable', 'Hashable', 'Immutable' ) ) ) {
		require_once __DIR__ . '/interfaces/' . $className . '.php';
		return;
	}

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

	if ( $namespaceSegments[0] === 'DataValues' ) {
		if ( count( $namespaceSegments ) === 1 || $namespaceSegments[1] !== 'Tests' ) {
			$fileName = __DIR__ . '/src/' . substr( $fileName, 11 );

			// FIXME: this causes reads for lookups of files in the DataValues NS defined elsewhere
			if ( is_readable( $fileName ) ) {
				require_once $fileName;
			}
		}
	}
} );

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/DataValues.mw.php';
}

global $wgDataValues;
/**
 * @deprecated since 0.1 This is a global registry that provides no control over object lifecycle
 */
$wgDataValues = array();

$wgDataValues['boolean'] = 'DataValues\BooleanValue';
$wgDataValues['globecoordinate'] = 'DataValues\GlobeCoordinateValue';
$wgDataValues['iri'] = 'DataValues\IriValue';
$wgDataValues['monolingualtext'] = 'DataValues\MonolingualTextValue';
$wgDataValues['multilingualtext'] = 'DataValues\MultilingualTextValue';
$wgDataValues['number'] = 'DataValues\NumberValue';
$wgDataValues['quantity'] = 'DataValues\QuantityValue';
$wgDataValues['string'] = 'DataValues\StringValue';
$wgDataValues['time'] = 'DataValues\TimeValue';
$wgDataValues['unknown'] = 'DataValues\UnknownValue';