<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match(
		'+^(.*?)' . preg_quote( DIRECTORY_SEPARATOR ) . '(vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '(.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '../' . $remoteExtPathParts[2]
			. DIRECTORY_SEPARATOR . $remoteExtPathParts[3],
	);

	$modules = array(

		'wikibase.serialization.ClaimsUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimsUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ClaimUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ReferenceUnserializer',
				'wikibase.serialization.SnakListUnserializer',
				'wikibase.serialization.SnakUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.EntityIdUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.EntityUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntityUnserializer.js',
				'EntityUnserializer.itemExpert.js',
				'EntityUnserializer.propertyExpert.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimsUnserializer',
				'wikibase.serialization.MultilingualUnserializer',
				'wikibase.serialization.SiteLinkUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.MultilingualUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultilingualUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ReferenceUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SiteLinkUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SnakListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SnakUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakUnserializer.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.values',
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.TermListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermUnserializer',
			),
		),

		'wikibase.serialization.TermUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.Unserializer' => $moduleTemplate + array(
			'scripts' => array(
				'Unserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
			),
		),

	);

	return $modules;
} );
