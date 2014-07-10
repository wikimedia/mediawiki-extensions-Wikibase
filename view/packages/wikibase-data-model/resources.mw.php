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
		'+^(.*?)(' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'src',
		'remoteExtPath' => '..' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . 'src',
	);

	$modules = array(
		'wikibase.datamodel' => $moduleTemplate + array(
			'scripts' => array(
				'Claim.js',
				'Entity.js',
				'EntityId.js',
				'Item.js',
				'Property.js',
				'Reference.js',
				'SnakList.js',
				'Statement.js',
			),
			'dependencies' => array(
				// Used by wikibase.Claim, wikibase.Entity, wikibase.Reference, wikibase.SnakList,
				// wikibase.Statement
				// Methods: $.each, $.extend, $.inArray, $.isArray, $.isPlainObject
				'jquery',

				// Used by wikibase.EntityId
				'mw.ext.dataValues', // DataValues extension

				'util.inherit',

				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.Snak.newFromMap'
			)
		),

		'wikibase.datamodel.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js',
			),
			'dependencies' => array(
				'wikibase', // Just for the namespace
			)
		),

		'wikibase.datamodel.PropertyNoValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertyNoValueSnak.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak'
			)
		),

		'wikibase.datamodel.PropertySomeValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertySomeValueSnak.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak'
			)
		),

		'wikibase.datamodel.PropertyValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertyValueSnak.js',
			),
			'dependencies' => array(
				'mw.ext.dataValues', // DataValues extension
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak'
			)
		),

		'wikibase.datamodel.Snak' => $moduleTemplate + array(
			'scripts' => array(
				'Snak.js',
			),
			'dependencies' => array(
				'jquery', // $.each, $.extend

				'mw.ext.dataValues', // DataValues extension

				'util.inherit',

				'wikibase.datamodel.__namespace',
			)
		),

		'wikibase.datamodel.Snak.newFromMap' => $moduleTemplate + array(
			'scripts' => array(
				'Snak.newFromMap.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Snak',
			)
		),
	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
