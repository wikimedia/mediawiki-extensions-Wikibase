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

		'wikibase.serialization.ClaimListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.serialization.ClaimListUnserializer',
			),
		),

		'wikibase.serialization.ClaimsUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimsUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimsUnserializer',
			),
		),

		'wikibase.serialization.ClaimUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.ClaimUnserializer',
			),
		),

		'wikibase.serialization.EntityIdUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityIdUnserializer',
			),
		),

		'wikibase.serialization.EntityUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'EntityUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityUnserializer',
			),
		),

		'wikibase.serialization.FingerprintUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'FingerprintUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.FingerprintUnserializer',
			),
		),

		'wikibase.serialization.MultilingualUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'MultilingualUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.serialization.MultilingualUnserializer',
			),
		),

		'wikibase.serialization.ReferenceListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.ReferenceListUnserializer',
			),
		),

		'wikibase.serialization.ReferenceUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceUnserializer',
			),
		),

		'wikibase.serialization.SiteLinkListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkList',
				'wikibase.serialization.SiteLinkListUnserializer',
			),
		),

		'wikibase.serialization.SiteLinkUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SiteLinkUnserializer',
			),
		),

		'wikibase.serialization.SnakListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SnakListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SnakListUnserializer',
			),
		),

		'wikibase.serialization.SnakUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SnakUnserializer.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.SnakUnserializer',
			),
		),

		'wikibase.serialization.StatementListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementListUnserializer',
			),
		),

		'wikibase.serialization.StatementUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.StatementUnserializer',
			),
		),

		'wikibase.serialization.TermGroupListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.serialization.TermGroupListUnserializer',
			),
		),

		'wikibase.serialization.TermGroupUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.TermGroup',
				'wikibase.serialization.TermGroupUnserializer',
			),
		),

		'wikibase.serialization.TermListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.TermListUnserializer',
			),
		),

		'wikibase.serialization.TermUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Term',
				'wikibase.serialization.TermUnserializer',
			),
		),

		'wikibase.serialization.Unserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Unserializer.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Unserializer',
			),
		),

	);

	return $modules;
} );
