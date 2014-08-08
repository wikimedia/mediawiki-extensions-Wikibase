<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
 * File for Wikibase resourceloader modules.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	global $wgResourceModules;

	preg_match(
		'+^(.*?)' . preg_quote( DIRECTORY_SEPARATOR ) . '(vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '(.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '../' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . $remoteExtPathParts[3],
	);

	$modules = array(
		'wikibase.serialization' => $moduleTemplate + array(
			'scripts' => array(
				'src/serialization.js',
				'src/serialization.Serializer.js',
				'src/serialization.Unserializer.js',
				'src/serialization.SerializerFactory.js',
			),
			'dependencies' => array(
				'jquery',
				'util.inherit',
				'wikibase',
			)
		),

		'wikibase.serialization.entities' => $moduleTemplate + array(
			'scripts' => array(
				'src/serialization.EntityUnserializer.js',
				'src/serialization.EntityUnserializer.propertyExpert.js',
			),
			'dependencies' => array(
				'jquery',
				'util.inherit',
				'wikibase.serialization',
				'wikibase.datamodel',
			)
		),
	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
