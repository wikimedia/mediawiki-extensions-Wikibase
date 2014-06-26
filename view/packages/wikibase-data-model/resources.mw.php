<?php

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
		'wikibase.datamodel' => $moduleTemplate + array(
			'scripts' => array(
				'src/datamodel.entities/wikibase.Entity.js',
				'src/datamodel.entities/wikibase.Item.js',
				'src/datamodel.entities/wikibase.Property.js',
				'src/wikibase.EntityId.js',
				'src/wikibase.Snak.js',
				'src/wikibase.SnakList.js',
				'src/wikibase.PropertyValueSnak.js',
				'src/wikibase.PropertySomeValueSnak.js',
				'src/wikibase.PropertyNoValueSnak.js',
				'src/wikibase.Reference.js',
				'src/wikibase.Claim.js',
				'src/wikibase.Statement.js',
			),
			'dependencies' => array(
				'jquery', // wikibase.Claim
				'util.inherit',
				'wikibase', // What? Just for the namespace?
				'mw.ext.dataValues', // DataValues extension
			)
		),
	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
