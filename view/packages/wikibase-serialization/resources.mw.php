<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
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
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'src',
		'remoteExtPath' => '../' . $remoteExtPathParts[2]
			. DIRECTORY_SEPARATOR . $remoteExtPathParts[3]
			. DIRECTORY_SEPARATOR . 'src',
	);

	$modules = array(

		'wikibase.serialization' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.init.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimUnserializer',
				'wikibase.serialization.EntityUnserializer',
				'wikibase.serialization.ReferenceUnserializer',
				'wikibase.serialization.SerializerFactory',
				'wikibase.serialization.SnakListUnserializer',
			),
		),

		'wikibase.serialization.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.__namespace.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),

		'wikibase.serialization.ClaimsSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimsSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ClaimsUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimsUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ClaimSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.SnakSerializer',
			),
		),

		'wikibase.serialization.ClaimUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimUnserializer.js',
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

		'wikibase.serialization.EntityIdSerializer' => $moduleTemplate + array(
				'scripts' => array(
					'serialization.EntityIdSerializer.js',
				),
				'dependencies' => array(
					'util.inherit',
					'wikibase.datamodel',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
				),
			),

		'wikibase.serialization.EntityIdUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityIdUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.EntitySerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntitySerializer.js',
				'serialization.EntitySerializer.itemExpert.js',
				'serialization.EntitySerializer.propertyExpert.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimsSerializer',
				'wikibase.serialization.MultilingualSerializer',
				'wikibase.serialization.SiteLinkSerializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.EntityUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityUnserializer.js',
				'serialization.EntityUnserializer.itemExpert.js',
				'serialization.EntityUnserializer.propertyExpert.js',
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

		'wikibase.serialization.MultilingualSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.MultilingualSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.MultilingualUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.MultilingualUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ReferenceSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ReferenceSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ReferenceUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ReferenceUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SiteLinkSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SiteLinkSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SiteLinkUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SiteLinkUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.Serializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.Serializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
			),
		),

		'wikibase.serialization.SerializerFactory' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SerializerFactory.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SnakListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SnakListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SnakSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SnakUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakUnserializer.js',
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

		'wikibase.serialization.TermListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.TermListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermSerializer',
			),
		),

		'wikibase.serialization.TermListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.TermListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermUnserializer',
			),
		),

		'wikibase.serialization.TermSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.TermSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.TermUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.TermUnserializer.js',
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
				'serialization.Unserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
			),
		),

	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
