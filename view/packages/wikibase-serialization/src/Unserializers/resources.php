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

		'wikibase.serialization.ClaimGroupSetUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupSetUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimGroupUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ClaimGroupUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimListUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ClaimListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ClaimUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Claim',
				'wikibase.serialization.__namespace',
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
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Property',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.FingerprintUnserializer',
				'wikibase.serialization.SiteLinkSetUnserializer',
				'wikibase.serialization.StatementGroupSetUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.FingerprintUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'FingerprintUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.TermSetUnserializer',
				'wikibase.serialization.MultiTermSetUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.MultiTermSetUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermSetUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.MultiTermSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.MultiTermUnserializer',
			),
		),

		'wikibase.serialization.MultiTermUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.MultiTerm',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.ReferenceListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ReferenceUnserializer',
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

		'wikibase.serialization.SiteLinkSetUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSetUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SiteLinkUnserializer',
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
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.StatementGroupSetUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupSetUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementGroupUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.StatementGroupUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementListUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.StatementListUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementListUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.StatementUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimUnserializer',
				'wikibase.serialization.ReferenceListUnserializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.TermSetUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermSetUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermSet',
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
