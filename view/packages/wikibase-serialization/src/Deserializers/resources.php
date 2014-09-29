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

		'wikibase.serialization.ClaimGroupSetDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupSetDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimGroupDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.ClaimGroupDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimListDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.ClaimListDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimListDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.ClaimDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Claim',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListDeserializer',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.EntityIdDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.EntityDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntityDeserializer.js',
				'EntityDeserializer.itemExpert.js',
				'EntityDeserializer.propertyExpert.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Property',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.FingerprintDeserializer',
				'wikibase.serialization.SiteLinkSetDeserializer',
				'wikibase.serialization.StatementGroupSetDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.FingerprintDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'FingerprintDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.TermSetDeserializer',
				'wikibase.serialization.MultiTermSetDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.MultiTermSetDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermSetDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.MultiTermSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.MultiTermDeserializer',
			),
		),

		'wikibase.serialization.MultiTermDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.MultiTerm',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.ReferenceListDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceListDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ReferenceDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.ReferenceDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.SiteLinkSetDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSetDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SiteLinkDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.SiteLinkDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.SnakListDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakListDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.SnakDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakDeserializer.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.values',
				'util.inherit',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.StatementGroupSetDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupSetDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementGroupDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.StatementGroupDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementListDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.StatementListDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementListDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.StatementDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimDeserializer',
				'wikibase.serialization.ReferenceListDeserializer',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.TermSetDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermSetDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermDeserializer',
			),
		),

		'wikibase.serialization.TermDeserializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermDeserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			),
		),

		'wikibase.serialization.Deserializer' => $moduleTemplate + array(
			'scripts' => array(
				'Deserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
			),
		),

	);

	return $modules;
} );
