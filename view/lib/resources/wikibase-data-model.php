<?php

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../wikibase-data-model/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-model/src',
	];

	return [
		'wikibase.datamodel' => $moduleTemplate + [
			'dependencies' => [
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.EntityId',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
			],
		],

		'wikibase.datamodel.__namespace' => $moduleTemplate + [
			'scripts' => [
				'__namespace.js',
			],
			'dependencies' => [
				'wikibase', // Just for the namespace
			],
		],

		'wikibase.datamodel.Claim' => $moduleTemplate + [
			'scripts' => [
				'Claim.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			],
		],

		'wikibase.datamodel.Entity' => $moduleTemplate + [
			'scripts' => [
				'Entity.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.FingerprintableEntity' => $moduleTemplate + [
			'scripts' => [
				'FingerprintableEntity.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Entity',
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.EntityId' => $moduleTemplate + [
			'scripts' => [
				'EntityId.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.Fingerprint' => $moduleTemplate + [
			'scripts' => [
				'Fingerprint.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.TermMap',
			],
		],

		'wikibase.datamodel.Group' => $moduleTemplate + [
			'scripts' => [
				'Group.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.GroupableCollection' => $moduleTemplate + [
			'scripts' => [
				'GroupableCollection.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.Item' => $moduleTemplate + [
			'scripts' => [
				'Item.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.FingerprintableEntity',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.datamodel.StatementGroupSet',
			],
		],

		'wikibase.datamodel.List' => $moduleTemplate + [
			'scripts' => [
				'List.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			],
		],

		'wikibase.datamodel.Map' => $moduleTemplate + [
			'scripts' => [
				'Map.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.MultiTerm' => $moduleTemplate + [
			'scripts' => [
				'MultiTerm.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.MultiTermMap' => $moduleTemplate + [
			'scripts' => [
				'MultiTermMap.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Map',
				'wikibase.datamodel.MultiTerm',
			],
		],

		'wikibase.datamodel.Property' => $moduleTemplate + [
			'scripts' => [
				'Property.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.FingerprintableEntity',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.StatementGroupSet',
			],
		],

		'wikibase.datamodel.PropertyNoValueSnak' => $moduleTemplate + [
			'scripts' => [
				'PropertyNoValueSnak.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.PropertySomeValueSnak' => $moduleTemplate + [
			'scripts' => [
				'PropertySomeValueSnak.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.PropertyValueSnak' => $moduleTemplate + [
			'scripts' => [
				'PropertyValueSnak.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.Reference' => $moduleTemplate + [
			'scripts' => [
				'Reference.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SnakList',
			],
		],

		'wikibase.datamodel.ReferenceList' => $moduleTemplate + [
			'scripts' => [
				'ReferenceList.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
				'wikibase.datamodel.Reference',
			],
		],

		'wikibase.datamodel.SiteLink' => $moduleTemplate + [
			'scripts' => [
				'SiteLink.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.SiteLinkSet' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkSet.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.Set',
			],
		],

		'wikibase.datamodel.Snak' => $moduleTemplate + [
			'scripts' => [
				'Snak.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.SnakList' => $moduleTemplate + [
			'scripts' => [
				'SnakList.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.Statement' => $moduleTemplate + [
			'scripts' => [
				'Statement.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ReferenceList',
			],
		],

		'wikibase.datamodel.StatementGroup' => $moduleTemplate + [
			'scripts' => [
				'StatementGroup.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Group',
				'wikibase.datamodel.StatementList',
			],
		],

		'wikibase.datamodel.StatementGroupSet' => $moduleTemplate + [
			'scripts' => [
				'StatementGroupSet.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.Set',
			],
		],

		'wikibase.datamodel.StatementList' => $moduleTemplate + [
			'scripts' => [
				'StatementList.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
				'wikibase.datamodel.Statement',
			],
		],

		'wikibase.datamodel.Term' => $moduleTemplate + [
			'scripts' => [
				'Term.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.TermMap' => $moduleTemplate + [
			'scripts' => [
				'TermMap.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Map',
				'wikibase.datamodel.Term',
			],
		],

		'wikibase.datamodel.Set' => $moduleTemplate + [
			'scripts' => [
				'Set.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			],
		],

	];
} );
