<?php

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
	$wikibaseDatamodelPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-model/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-model/src',
	];
	$wikibaseDatavaluesLibTemplate = [
		'localBasePath' => __DIR__ . '/wikibase-data-values/lib',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/lib',
	];
	$wikibaseDatavaluesSrcTemplate = [
		'localBasePath' => __DIR__ . '/wikibase-data-values/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src',
	];

	$modules = [
		'wikibase.datamodel' => [
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

		'wikibase.datamodel.__namespace' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'__namespace.js',
			],
			'dependencies' => [
				'wikibase', // Just for the namespace
			],
		],

		'wikibase.datamodel.Claim' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Claim.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			],
		],

		'wikibase.datamodel.Entity' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Entity.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.FingerprintableEntity' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'FingerprintableEntity.js',
			],
			'dependencies' => [
				'wikibase.datamodel.Entity',
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.EntityId' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'EntityId.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.Fingerprint' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Fingerprint.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.TermMap',
			],
		],

		'wikibase.datamodel.Group' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Group.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.GroupableCollection' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'GroupableCollection.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.Item' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.List' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'List.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			],
		],

		'wikibase.datamodel.Map' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Map.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.MultiTerm' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'MultiTerm.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.MultiTermMap' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Property' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.PropertyNoValueSnak' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'PropertyNoValueSnak.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.PropertySomeValueSnak' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'PropertySomeValueSnak.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Snak',
			],
		],

		'wikibase.datamodel.PropertyValueSnak' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Reference' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Reference.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SnakList',
			],
		],

		'wikibase.datamodel.ReferenceList' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.SiteLink' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'SiteLink.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.SiteLinkSet' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Snak' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Snak.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.SnakList' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Statement' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Statement.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ReferenceList',
			],
		],

		'wikibase.datamodel.StatementGroup' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.StatementGroupSet' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.StatementList' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Term' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Term.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
		],

		'wikibase.datamodel.TermMap' => $wikibaseDatamodelPaths + [
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

		'wikibase.datamodel.Set' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Set.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.GroupableCollection',
			],
		],

		'globeCoordinate.js' => $wikibaseDatavaluesLibTemplate + [
			'scripts' => [
				'globeCoordinate/globeCoordinate.js',
				'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			],
		],
		'util.inherit' => $wikibaseDatavaluesLibTemplate + [
			'scripts' => [
				'util/util.inherit.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'dataValues.js',
			],
		],

		'dataValues.DataValue' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'DataValue.js',
			],
			'dependencies' => [
				'dataValues',
				'util.inherit',
			],
		],

		'dataValues.values' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				// Note: The order here is relevant, scripts should be places after the ones they
				//  depend on.
				'values/BoolValue.js',
				'values/DecimalValue.js',
				'values/GlobeCoordinateValue.js',
				'values/MonolingualTextValue.js',
				'values/MultilingualTextValue.js',
				'values/StringValue.js',
				'values/NumberValue.js',
				'values/TimeValue.js',
				'values/QuantityValue.js',
				'values/UnknownValue.js',
				'values/UnDeserializableValue.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'dataValues.TimeValue',
				'globeCoordinate.js', // required by GlobeCoordinateValue
				'util.inherit',
			],
		],

		'dataValues.TimeValue' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'values/TimeValue.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
			],
		],

		'valueFormatters' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueFormatters/valueFormatters.js',
			],
		],

		'valueFormatters.ValueFormatter' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueFormatters/formatters/ValueFormatter.js',
			],
			'dependencies' => [
				'util.inherit',
				'valueFormatters',
			],
		],

		'valueFormatters.formatters' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueFormatters/formatters/NullFormatter.js',
				'valueFormatters/formatters/StringFormatter.js',
			],
			'dependencies' => [
				'dataValues.values',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			],
		],
		'valueParsers' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueParsers/valueParsers.js',
			],
		],

		'valueParsers.ValueParser' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueParsers/parsers/ValueParser.js',
			],
			'dependencies' => [
				'util.inherit',
				'valueParsers',
			],
		],

		'valueParsers.ValueParserStore' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueParsers/ValueParserStore.js',
			],
			'dependencies' => [
				'valueParsers',
			],
		],

		'valueParsers.parsers' => $wikibaseDatavaluesSrcTemplate + [
			'scripts' => [
				'valueParsers/parsers/BoolParser.js',
				'valueParsers/parsers/FloatParser.js',
				'valueParsers/parsers/IntParser.js',
				'valueParsers/parsers/NullParser.js',
				'valueParsers/parsers/StringParser.js',
			],
			'dependencies' => [
				'dataValues.values',
				'util.inherit',
				'valueParsers.ValueParser',
			],
		],
	];
	return array_merge(
		$modules,
		require __DIR__ . '/resources/wikibase-serialization.php',
		require __DIR__ . '/resources/wikibase-api.php',
		require __DIR__ . '/resources/wikibase-data-values-value-view.php',
		require __DIR__ . '/resources/wikibase-termbox.php'
	);
} );
