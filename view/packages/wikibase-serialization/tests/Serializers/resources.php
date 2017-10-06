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
		'wikibase.serialization.ClaimSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ClaimSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.ClaimSerializer',
			],
		],

		'wikibase.serialization.EntitySerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'EntitySerializer.tests.js',
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
				'wikibase.serialization.EntitySerializer',
				'wikibase.serialization.tests.MockEntity',
			],
		],

		'wikibase.serialization.FingerprintSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'FingerprintSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.FingerprintSerializer',
			],
		],

		'wikibase.serialization.ItemSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ItemSerializer.tests.js',
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
				'wikibase.serialization.ItemSerializer',
			],
		],

		'wikibase.serialization.MultiTermMapSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'MultiTermMapSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Term',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.serialization.MultiTermMapSerializer',
			],
		],

		'wikibase.serialization.MultiTermSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'MultiTermSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.MultiTerm',
				'wikibase.serialization.MultiTermSerializer',
			],
		],

		'wikibase.serialization.PropertySerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'PropertySerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
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
				'wikibase.serialization.PropertySerializer',
			],
		],

		'wikibase.serialization.ReferenceListSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ReferenceListSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.ReferenceListSerializer',
			],
		],

		'wikibase.serialization.ReferenceSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'ReferenceSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceSerializer',
			],
		],

		'wikibase.serialization.SiteLinkSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.SiteLinkSerializer',
			],
		],

		'wikibase.serialization.SiteLinkSetSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkSetSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.SiteLinkSetSerializer',
			],
		],

		'wikibase.serialization.Serializer.tests' => $moduleTemplate + [
			'scripts' => [
				'Serializer.tests.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.serialization.Serializer',
			],
		],

		'wikibase.serialization.SnakListSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SnakListSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.serialization.SnakListSerializer',
			],
		],

		'wikibase.serialization.SnakSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'SnakSerializer.tests.js',
			],
			'dependencies' => [
				'dataValues.values',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.SnakSerializer',
			],
		],

		'wikibase.serialization.StatementGroupSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementGroupSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementGroupSerializer',
			],
		],

		'wikibase.serialization.StatementGroupSetSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementGroupSetSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementGroupSetSerializer',
			],
		],

		'wikibase.serialization.StatementListSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementListSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.StatementListSerializer',
			],
		],

		'wikibase.serialization.StatementSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'StatementSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.StatementSerializer',
			],
		],

		'wikibase.serialization.TermSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'TermSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Term',
				'wikibase.serialization.TermSerializer',
			],
		],

		'wikibase.serialization.TermMapSerializer.tests' => $moduleTemplate + [
			'scripts' => [
				'TermMapSerializer.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.TermMapSerializer',
			],
		],

	];

	return $modules;
} );
