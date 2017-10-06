<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [
		'wikibase.serialization.ClaimDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ClaimDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.ClaimDeserializer',
			],
		],

		'wikibase.serialization.Deserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'Deserializer.tests.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.serialization.Deserializer',
			],
		],

		'wikibase.serialization.EntityDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'EntityDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.EntityDeserializer',
				'wikibase.serialization.tests.MockEntity',
			],
		],

		'wikibase.serialization.FingerprintDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'FingerprintDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.FingerprintDeserializer',
			],
		],

		'wikibase.serialization.ItemDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ItemDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.ItemDeserializer',
			],
		],

		'wikibase.serialization.MultiTermMapDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'MultiTermMapDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.serialization.MultiTermMapDeserializer',
			],
		],

		'wikibase.serialization.MultiTermDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'MultiTermDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.MultiTerm',
				'wikibase.serialization.MultiTermDeserializer',
			],
		],

		'wikibase.serialization.PropertyDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'PropertyDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.PropertyDeserializer',
			],
		],

		'wikibase.serialization.ReferenceListDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ReferenceListDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.ReferenceListDeserializer',
			],
		],

		'wikibase.serialization.ReferenceDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ReferenceDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceDeserializer',
			],
		],

		'wikibase.serialization.SiteLinkSetDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkSetDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.SiteLinkSetDeserializer',
			],
		],

		'wikibase.serialization.SiteLinkDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.SiteLinkDeserializer',
			],
		],

		'wikibase.serialization.SnakListDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SnakListDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.SnakListDeserializer',
			],
		],

		'wikibase.serialization.SnakDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SnakDeserializer.tests.js',
			],
			'dependencies' => [
				'dataValues.values',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.SnakDeserializer',
			],
		],

		'wikibase.serialization.StatementGroupSetDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementGroupSetDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementGroupSetDeserializer',
			],
		],

		'wikibase.serialization.StatementGroupDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementGroupDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementGroupDeserializer',
			],
		],

		'wikibase.serialization.StatementListDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementListDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementListDeserializer',
			],
		],

		'wikibase.serialization.StatementDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.StatementDeserializer',
			],
		],

		'wikibase.serialization.TermDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'TermDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Term',
				'wikibase.serialization.TermDeserializer',
			],
		],

		'wikibase.serialization.TermMapDeserializer.tests' => $moduleTemplate + [
			'scripts' => [
				'TermMapDeserializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.TermMapDeserializer',
			],
		],

	];

	return $modules;
} );
