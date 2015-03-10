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

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'src',
		'remoteExtPath' => '..' . $remoteExtPath[0] . DIRECTORY_SEPARATOR . 'src',
	);

	$modules = array(
		'wikibase.datamodel' => $moduleTemplate + array(
			'dependencies' => array(
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.ClaimGroupSet',
				'wikibase.datamodel.ClaimList',
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
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.ClaimList',
				'wikibase.datamodel.Group',
			),
		),

		'wikibase.datamodel.ClaimGroupSet' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimGroupSet.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.ClaimGroup',
				'wikibase.datamodel.Set',
			),
		),

		'wikibase.datamodel.ClaimList' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimList.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.List',
			),
		),

		'wikibase.datamodel.Entity' => $moduleTemplate + array(
			'scripts' => array(
				'Entity.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.EntityId' => $moduleTemplate + array(
			'scripts' => array(
				'EntityId.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
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
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.TermMap',
			),
		),

		'wikibase.datamodel.Group' => $moduleTemplate + array(
			'scripts' => array(
				'Group.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.GroupableCollection' => $moduleTemplate + array(
			'scripts' => array(
				'GroupableCollection.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
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
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.datamodel.StatementGroupSet',
			),
		),

		'wikibase.datamodel.List' => $moduleTemplate + array(
			'scripts' => array(
				'List.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			),
		),

		'wikibase.datamodel.Map' => $moduleTemplate + array(
			'scripts' => array(
				'Map.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.MultiTerm' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTerm.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.__namespace',
			),
		),

		'wikibase.datamodel.MultiTermMap' => $moduleTemplate + array(
			'scripts' => array(
				'MultiTermMap.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Map',
				'wikibase.datamodel.MultiTerm',
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
				'wikibase.datamodel.StatementGroupSet',
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
				'dataValues.DataValue',
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
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
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

		'wikibase.datamodel.SiteLinkSet' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSet.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.Set',
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
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
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
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Group',
				'wikibase.datamodel.StatementList',
			),
		),

		'wikibase.datamodel.StatementGroupSet' => $moduleTemplate + array(
			'scripts' => array(
				'StatementGroupSet.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.Set',
			),
		),

		'wikibase.datamodel.StatementList' => $moduleTemplate + array(
			'scripts' => array(
				'StatementList.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.List',
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

		'wikibase.datamodel.TermMap' => $moduleTemplate + array(
			'scripts' => array(
				'TermMap.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Map',
				'wikibase.datamodel.Term',
			),
		),

		'wikibase.datamodel.Set' => $moduleTemplate + array(
			'scripts' => array(
				'Set.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			),
		),

	);

	$wgResourceModules = array_merge( $wgResourceModules, $modules );
} );
