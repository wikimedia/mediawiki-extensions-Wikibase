<?php

global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
	preg_match(
		'+^(.*?)(' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'tests',
		'remoteExtPath' => '..' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . 'tests',
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

		'wikibase.datamodel.ClaimGroupList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.ClaimGroupList',
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

		'wikibase.datamodel.Fingerprint.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Fingerprint.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
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
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkList',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
			),
		),

		'wikibase.datamodel.Property.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Property.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
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
				'Reference.tests.js',
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

		'wikibase.datamodel.SiteLinkList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkList',
			),
		),

		'wikibase.datamodel.Snak.tests' => $moduleTemplate + array(
			'scripts' => array(
				'Snak.tests.js',
			),
			'dependencies' => array(
				'mw.ext.dataValues',
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
				'mw.ext.dataValues',
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

		'wikibase.datamodel.StatementGroupList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupList',
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

		'wikibase.datamodel.TermGroup.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroup.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.TermGroup',
			),
		),

		'wikibase.datamodel.TermGroupList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
			),
		),

		'wikibase.datamodel.TermList.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TermList.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermList',
			),
		),

	);

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		$modules
	);

	return true;
};
