<?php

global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'tests',
		'remoteExtPath' => '..' . $remoteExtPath[0] . DIRECTORY_SEPARATOR . 'tests',
	);

	$modules = array(

		'wikibase.datamodel.Claim.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Claim.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.ClaimGroup.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroup.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
			),
		),

		'wikibase.datamodel.ClaimGroupSet.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupSet.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.ClaimGroupSet',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
			),
		),

		'wikibase.datamodel.ClaimList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.EntityId.tests' => $moduleTemplate + array(
			'scripts' => array(
				'EntityId.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.EntityId',
			),
		),

		'wikibase.datamodel.Fingerprint.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Fingerprint.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
			),
		),

		'wikibase.datamodel.Group.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Group.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Group',
				'wikibase.datamodel.GroupableCollection',
			),
		),

		'wikibase.datamodel.Item.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Item.tests.js',
			),
			'dependencies' => array(
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
			),
		),

		'wikibase.datamodel.List.tests' => $moduleTemplate + array(
			'scripts' => array(
				'List.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.List',
			),
		),

		'wikibase.datamodel.Map.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Map.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Map',
			),
		),

		'wikibase.datamodel.MultiTerm.tests' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTerm.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.MultiTerm',
			),
		),

		'wikibase.datamodel.MultiTermMap.tests' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermMap.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
			),
		),

		'wikibase.datamodel.Property.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Property.tests.js',
			),
			'dependencies' => array(
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
			),
		),

		'wikibase.datamodel.Reference.tests' => $moduleTemplate + array(
				'scripts' => array(
					'Reference.tests.js',
				),
				'dependencies' => array(
					'wikibase.datamodel.PropertyNoValueSnak',
					'wikibase.datamodel.PropertySomeValueSnak',
					'wikibase.datamodel.Reference',
					'wikibase.datamodel.SnakList',
				),
			),

		'wikibase.datamodel.ReferenceList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.SiteLink.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLink.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.SiteLink',
			),
		),

		'wikibase.datamodel.SiteLinkSet.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSet.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
			),
		),

		'wikibase.datamodel.Snak.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Snak.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Snak',
			),
		),

		'wikibase.datamodel.SnakList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SnakList.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.Statement.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Statement.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
			),
		),

		'wikibase.datamodel.StatementGroup.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroup.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.StatementGroupSet.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupSet.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.StatementList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.Term.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Term.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Term',
			),
		),

		'wikibase.datamodel.TermMap.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermMap.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
			),
		),

		'wikibase.datamodel.Set.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Set.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Set',
			),
		),

	);

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		$modules
	);

	return true;
};
