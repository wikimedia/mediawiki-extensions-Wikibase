<?php

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
		'+^(.*?)(' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'src',
		'remoteExtPath' => '..' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . 'src',
	);

	$modules = array(
		'wikibase.datamodel' => $moduleTemplate + array(
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.ClaimGroupList',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.EntityId',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkList',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupList',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermGroup',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
			),
		),

		'wikibase.datamodel.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js',
			),
			'dependencies' => array(
				'wikibase', // Just for the namespace
			),
		),

		'wikibase.datamodel.Claim' => $moduleTemplate + array(
			'scripts' => array(
				'Claim.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.ClaimGroup' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroup.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.ClaimList',
			),
		),

		'wikibase.datamodel.ClaimGroupList' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.ClaimGroup',
			),
		),

		'wikibase.datamodel.ClaimList' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Claim',
			),
		),

		'wikibase.datamodel.Entity' => $moduleTemplate + array(
			'scripts' => array(
				'Entity.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.EntityId' => $moduleTemplate + array(
			'scripts' => array(
				'EntityId.js',
			),
			'dependencies' => array(
				'mw.ext.dataValues',
				'util.inherit',
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.Fingerprint' => $moduleTemplate + array(
			'scripts' => array(
				'Fingerprint.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.TermGroupList',
				'wikibase.datamodel.TermList',
			),
		),

		'wikibase.datamodel.Item' => $moduleTemplate + array(
			'scripts' => array(
				'Item.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.SiteLinkList',
				'wikibase.datamodel.StatementGroupList',
			),
		),

		'wikibase.datamodel.Property' => $moduleTemplate + array(
			'scripts' => array(
				'Property.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.PropertyNoValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertyNoValueSnak.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			),
		),

		'wikibase.datamodel.PropertySomeValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertySomeValueSnak.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			),
		),

		'wikibase.datamodel.PropertyValueSnak' => $moduleTemplate + array(
			'scripts' => array(
				'PropertyValueSnak.js',
			),
			'dependencies' => array(
				'mw.ext.dataValues',
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			),
		),

		'wikibase.datamodel.Reference' => $moduleTemplate + array(
			'scripts' => array(
				'Reference.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SnakList',
			),
		),

		'wikibase.datamodel.ReferenceList' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Reference',
			),
		),

		'wikibase.datamodel.SiteLink' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLink.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.SiteLinkList' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SiteLink',
			),
		),

		'wikibase.datamodel.Snak' => $moduleTemplate + array(
			'scripts' => array(
				'Snak.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.SnakList' => $moduleTemplate + array(
			'scripts' => array(
				'SnakList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			),
		),

		'wikibase.datamodel.Statement' => $moduleTemplate + array(
			'scripts' => array(
				'Statement.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ReferenceList',
			),
		),

		'wikibase.datamodel.StatementGroup' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroup.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.StatementGroupList' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.StatementGroup',
			),
		),

		'wikibase.datamodel.StatementList' => $moduleTemplate + array(
			'scripts' => array(
				'StatementList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Statement',
			),
		),

		'wikibase.datamodel.Term' => $moduleTemplate + array(
			'scripts' => array(
				'Term.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.TermGroup' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroup.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.TermGroupList' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.TermGroup',
			),
		),

		'wikibase.datamodel.TermList' => $moduleTemplate + array(
			'scripts' => array(
				'TermList.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Term',
			),
		),
	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
