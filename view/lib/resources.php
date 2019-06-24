<?php

use Wikibase\View\Termbox\TermboxModule;

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
	$wikibaseDatamodelPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-model/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-model/src',
	];
	$wikibaseDatavaluesLibPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-values/lib',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/lib',
	];
	$wikibaseDatavaluesSrcPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-values/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src',
	];
	$wikibaseSerializationPaths = [
		'localBasePath' => __DIR__ . '/wikibase-serialization/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-serialization/src',
	];
	$wikibaseApiPaths = [
		'localBasePath' => __DIR__ . '/wikibase-api/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-api/src',
	];
	$wikibaseDatavaluesValueviewLibPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-values-value-view/lib',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/lib',
	];
	$wikibaseDatavaluesValueviewSrcPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-values-value-view/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/src',
	];
	$wikibaseTermboxPaths = [
		'localBasePath' => __DIR__ . '/wikibase-termbox',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-termbox',
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.Entity' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Entity.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.Group' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Group.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.GroupableCollection' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'GroupableCollection.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.Map' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Map.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.MultiTerm' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'MultiTerm.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.Reference' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Reference.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
				'wikibase.datamodel.SnakList',
			],
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.datamodel.Term' => $wikibaseDatamodelPaths + [
			'scripts' => [
				'Term.js',
			],
			'dependencies' => [
				'wikibase.datamodel.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'globeCoordinate.js' => $wikibaseDatavaluesLibPaths + [
			'scripts' => [
				'globeCoordinate/globeCoordinate.js',
				'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],
		'util.inherit' => $wikibaseDatavaluesLibPaths + [
			'scripts' => [
				'util/util.inherit.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'dataValues.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues.DataValue' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'DataValue.js',
			],
			'dependencies' => [
				'dataValues',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues.values' => $wikibaseDatavaluesSrcPaths + [
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
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues.TimeValue' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'values/TimeValue.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'valueFormatters' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'valueFormatters/valueFormatters.js',
				'valueFormatters/formatters/ValueFormatter.js',
			],
			'dependencies' => [
				'util.inherit',
			],
		],

		'valueParsers' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'valueParsers/valueParsers.js',
			],
		],

		'valueParsers.ValueParserStore' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'valueParsers/ValueParserStore.js',
			],
			'dependencies' => [
				'valueParsers',
			],
		],

		// FIXME: This module is registered on WikibaseClient on all page views,
		// but only used in WikibaseRepo.
		'valueParsers.parsers' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'valueParsers/parsers/ValueParser.js',

				'valueParsers/parsers/BoolParser.js',
				'valueParsers/parsers/FloatParser.js',
				'valueParsers/parsers/IntParser.js',
				'valueParsers/parsers/NullParser.js',
				'valueParsers/parsers/StringParser.js',
			],
			'dependencies' => [
				'dataValues.values',
				'util.inherit',
				'valueParsers',
			],
		],

		'wikibase.serialization' => $wikibaseSerializationPaths + [
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.DeserializerFactory',
					'wikibase.serialization.SerializerFactory',
				],
			],

		'wikibase.serialization.__namespace' => $wikibaseSerializationPaths + [
				'scripts' => [
					'__namespace.js',
				],
				'dependencies' => [
					'wikibase',
				],
				'targets' => [ 'desktop', 'mobile' ],
			],

		'wikibase.serialization.DeserializerFactory' => $wikibaseSerializationPaths + [
				'scripts' => [
					'DeserializerFactory.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.StrategyProvider',

					'wikibase.serialization.ClaimDeserializer',
					'wikibase.serialization.EntityDeserializer',
					'wikibase.serialization.FingerprintDeserializer',
					'wikibase.serialization.MultiTermDeserializer',
					'wikibase.serialization.MultiTermMapDeserializer',
					'wikibase.serialization.ReferenceDeserializer',
					'wikibase.serialization.ReferenceListDeserializer',
					'wikibase.serialization.SiteLinkDeserializer',
					'wikibase.serialization.SiteLinkSetDeserializer',
					'wikibase.serialization.SnakDeserializer',
					'wikibase.serialization.SnakListDeserializer',
					'wikibase.serialization.StatementDeserializer',
					'wikibase.serialization.StatementGroupDeserializer',
					'wikibase.serialization.StatementGroupSetDeserializer',
					'wikibase.serialization.StatementListDeserializer',
					'wikibase.serialization.TermDeserializer',
					'wikibase.serialization.TermMapDeserializer',
				],
			],

		'wikibase.serialization.SerializerFactory' => $wikibaseSerializationPaths + [
				'scripts' => [
					'SerializerFactory.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StrategyProvider',

					'wikibase.serialization.ClaimSerializer',
					'wikibase.serialization.EntitySerializer',
					'wikibase.serialization.FingerprintSerializer',
					'wikibase.serialization.MultiTermMapSerializer',
					'wikibase.serialization.MultiTermSerializer',
					'wikibase.serialization.ReferenceListSerializer',
					'wikibase.serialization.ReferenceSerializer',
					'wikibase.serialization.SiteLinkSerializer',
					'wikibase.serialization.SiteLinkSetSerializer',
					'wikibase.serialization.SnakListSerializer',
					'wikibase.serialization.SnakSerializer',
					'wikibase.serialization.StatementGroupSerializer',
					'wikibase.serialization.StatementGroupSetSerializer',
					'wikibase.serialization.StatementListSerializer',
					'wikibase.serialization.StatementSerializer',
					'wikibase.serialization.TermMapSerializer',
					'wikibase.serialization.TermSerializer',
				],
			],

		'wikibase.serialization.StrategyProvider' => $wikibaseSerializationPaths + [
				'scripts' => [
					'StrategyProvider.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.ClaimDeserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/ClaimDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Claim',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.SnakListDeserializer',
					'wikibase.serialization.SnakDeserializer',
				],
				'targets' => [ 'desktop', 'mobile' ],
			],

		'wikibase.serialization.Deserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/Deserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.serialization.__namespace',
				],
				'targets' => [ 'desktop', 'mobile' ],
			],

		'wikibase.serialization.EntityDeserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/EntityDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.datamodel.Property',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.ItemDeserializer',
					'wikibase.serialization.PropertyDeserializer',
					'wikibase.serialization.StrategyProvider',
				],
			],

		'wikibase.serialization.FingerprintDeserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/FingerprintDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Fingerprint',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.MultiTermMapDeserializer',
					'wikibase.serialization.TermMapDeserializer',
				],
			],

		'wikibase.serialization.ItemDeserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/ItemDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.FingerprintDeserializer',
					'wikibase.serialization.SiteLinkSetDeserializer',
					'wikibase.serialization.StatementGroupSetDeserializer',
				],
			],

		'wikibase.serialization.MultiTermDeserializer' => $wikibaseSerializationPaths + [
				'scripts' => [
					'Deserializers/MultiTermDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.MultiTerm',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
				],
			],

		'wikibase.serialization.MultiTermMapDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/MultiTermMapDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.MultiTermDeserializer',
			],
		],

		'wikibase.serialization.PropertyDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/PropertyDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Property',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.FingerprintDeserializer',
				'wikibase.serialization.StatementGroupSetDeserializer',
			],
		],

		'wikibase.serialization.ReferenceListDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/ReferenceListDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.ReferenceDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.ReferenceDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/ReferenceDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Reference',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.SnakListDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.SiteLinkSetDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/SiteLinkSetDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.SiteLinkDeserializer',
			],
		],

		'wikibase.serialization.SiteLinkDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/SiteLinkDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SiteLink',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			],
		],

		'wikibase.serialization.SnakListDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/SnakListDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.SnakDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.SnakDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/SnakDeserializer.js',
			],
			'dependencies' => [
				'dataValues',
				'dataValues.values',
				'util.inherit',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementGroupSetDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/StatementGroupSetDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.StatementGroupDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementGroupDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/StatementGroupDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.StatementListDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementListDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/StatementListDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.StatementDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/StatementDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimDeserializer',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.ReferenceListDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.TermDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/TermDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.TermMapDeserializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Deserializers/TermMapDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.TermDeserializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.ClaimSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/ClaimSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Claim',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.SnakSerializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.EntitySerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/EntitySerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.Item',
				'wikibase.datamodel.Property',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ItemSerializer',
				'wikibase.serialization.PropertySerializer',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StrategyProvider',
			],
		],

		'wikibase.serialization.FingerprintSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/FingerprintSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.MultiTermMapSerializer',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermMapSerializer',
			],
		],

		'wikibase.serialization.ItemSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/ItemSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Item',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.FingerprintSerializer',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SiteLinkSetSerializer',
				'wikibase.serialization.StatementGroupSetSerializer',
			],
		],

		'wikibase.serialization.MultiTermMapSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/MultiTermMapSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.MultiTermSerializer',
				'wikibase.serialization.Serializer',
			],
		],

		'wikibase.serialization.MultiTermSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/MultiTermSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.MultiTerm',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			],
		],

		'wikibase.serialization.PropertySerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/PropertySerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Item',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.FingerprintSerializer',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StatementGroupSetSerializer',
			],
		],

		'wikibase.serialization.ReferenceListSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/ReferenceListSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ReferenceSerializer',
				'wikibase.serialization.Serializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.ReferenceSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/ReferenceSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Reference',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SnakListSerializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.Serializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/Serializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.serialization.__namespace',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.SiteLinkSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/SiteLinkSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SiteLink',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			],
		],

		'wikibase.serialization.SiteLinkSetSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/SiteLinkSetSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SiteLinkSerializer',
			],
		],

		'wikibase.serialization.SnakListSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/SnakListSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SnakSerializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.SnakSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/SnakSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementGroupSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/StatementGroupSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StatementListSerializer',
			],
		],

		'wikibase.serialization.StatementGroupSetSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/StatementGroupSetSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StatementGroupSerializer',
			],
		],

		'wikibase.serialization.StatementListSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/StatementListSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StatementSerializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.StatementSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/StatementSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.ReferenceListSerializer',
				'wikibase.serialization.Serializer',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.serialization.TermMapSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/TermMapSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermSerializer',
			],
		],

		'wikibase.serialization.TermSerializer' => $wikibaseSerializationPaths + [
			'scripts' => [
				'Serializers/TermSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			],
		],

		'wikibase.api.__namespace' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js'
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.FormatValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.api.getLocationAgnosticMwApi' => $wikibaseApiPaths + [
			'scripts' => [
				'getLocationAgnosticMwApi.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.ForeignApi',
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.ParseValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'ParseValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.api.RepoApi' => $wikibaseApiPaths + [
			'scripts' => [
				'RepoApi.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			]
		],

		'wikibase.api.RepoApiError' => $wikibaseApiPaths + [
			'scripts' => [
				'RepoApiError.js',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'jquery.animateWithEvent' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery/jquery.animateWithEvent.js',
			],
			'dependencies' => [
				'jquery.AnimationEvent',
			],
		],

		'jquery.AnimationEvent' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery/jquery.AnimationEvent.js',
			],
			'dependencies' => [
				'jquery.PurposedCallbacks',
			],
		],

		'jquery.focusAt' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery/jquery.focusAt.js',
			],
		],

		'jquery.inputautoexpand' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery/jquery.inputautoexpand.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
			],
		],

		'jquery.PurposedCallbacks' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery/jquery.PurposedCallbacks.js',
			],
		],

		'jquery.event.special.eachchange' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.event/jquery.event.special.eachchange.js'
			],
			'dependencies' => [
				'jquery.client',
			],
		],

		'jquery.ui.inputextender' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.inputextender.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.inputextender.css',
			],
			'dependencies' => [
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.listrotator' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.listrotator.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.listrotator.css',
			],
			'dependencies' => [
				'jquery.ui.autocomplete', // needs jquery.ui.menu
				'jquery.ui.widget',
				'jquery.ui.position',
			],
			'messages' => [
				'valueview-listrotator-manually',
			],
		],

		'jquery.ui.ooMenu' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.ooMenu.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.ooMenu.css',
			],
			'dependencies' => [
				'jquery.ui.widget',
				'jquery.util.getscrollbarwidth',
				'util.inherit',
			],
		],

		'jquery.ui.preview' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.preview.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.preview.css',
			],
			'dependencies' => [
				'jquery.ui.widget',
				'util.CombiningMessageProvider',
				'util.HashMessageProvider'
			],
		],

		'jquery.ui.suggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.suggester.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.suggester.css',
			],
			'dependencies' => [
				'jquery.ui.core',
				'jquery.ui.ooMenu',
				'jquery.ui.position',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.commonssuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.commonssuggester.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.commonssuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui.widget',
				'util.highlightSubstring',
			],
		],

		'jquery.ui.languagesuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.languagesuggester.js',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.toggler' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.toggler.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.toggler.css',
			],
			'dependencies' => [
				'jquery.animateWithEvent',
				'jquery.ui.core',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.unitsuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.unitsuggester.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.unitsuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui.widget',
			],
		],

		'jquery.util.adaptlettercase' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.util/jquery.util.adaptlettercase.js',
			],
		],

		'jquery.util.getscrollbarwidth' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.util/jquery.util.getscrollbarwidth.js',
			],
		],

		'util.ContentLanguages' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.ContentLanguages.js',
			],
			'dependencies' => [
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'util.Extendable' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.Extendable.js',
			],
		],

		'util.highlightSubstring' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.highlightSubstring.js',
			],
		],

		'util.MessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.MessageProvider.js',
			],
		],
		'util.HashMessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.HashMessageProvider.js',
			],
		],
		'util.CombiningMessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.CombiningMessageProvider.js',
			],
		],
		'util.PrefixingMessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.PrefixingMessageProvider.js',
			],
		],

		'util.Notifier' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.Notifier.js',
			],
		],

		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without losing any properties.
		'jquery.valueview' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.js',
			],
			'dependencies' => [
				'jquery.valueview.valueview',
			],
		],

		'jquery.valueview.Expert' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.Expert.js',
			],
			'dependencies' => [
				'util.inherit',
				'util.CombiningMessageProvider',
				'util.HashMessageProvider',
				'util.Notifier',
				'util.Extendable'
			],
		],

		'jquery.valueview.ExpertStore' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.ExpertStore.js',
			],
		],

		'jquery.valueview.experts' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.experts.js',
			],
		],

		// The actual valueview widget:
		'jquery.valueview.valueview' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.valueview.js',
			],
			'styles' => [
				'jquery.valueview.valueview.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'jquery.ui.widget',
				'jquery.valueview.ViewState',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.EmptyValue',
				'jquery.valueview.experts.UnsupportedValue',
				'util.Notifier',
				'valueFormatters',
				'valueParsers.ValueParserStore',
			],
		],

		'jquery.valueview.ViewState' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'jquery.valueview.ViewState.js',
			],
		],

		'jquery.valueview.experts.CommonsMediaType' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/CommonsMediaType.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.GeoShape' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/GeoShape.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.TabularData' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/TabularData.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.EmptyValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/EmptyValue.js',
			],
			'styles' => [
				'experts/EmptyValue.css',
			],
			'dependencies' => [
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			],
			'messages' => [
				'valueview-expert-emptyvalue-empty',
			],
		],

		'jquery.valueview.experts.GlobeCoordinateInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/GlobeCoordinateInput.js',
			],
			'styles' => [
				'experts/GlobeCoordinateInput.css',
			],
			'dependencies' => [
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.Container',
				'jquery.valueview.ExpertExtender.Listrotator',
				'jquery.valueview.ExpertExtender.Preview',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			],
			'messages' => [
				'valueview-expert-globecoordinateinput-precision',
				'valueview-expert-globecoordinateinput-nullprecision',
				'valueview-expert-globecoordinateinput-customprecision',
			],
		],

		'jquery.valueview.experts.MonolingualText' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/MonolingualText.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.LanguageSelector',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
			],
		],

		'jquery.valueview.experts.QuantityInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/QuantityInput.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.UnitSelector',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
			],
		],

		'jquery.valueview.experts.StringValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/StringValue.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.focusAt',
				'jquery.inputautoexpand',
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.SuggestedStringValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/SuggestedStringValue.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.suggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.TimeInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/TimeInput.js',
			],
			'styles' => [
				'experts/TimeInput.css',
			],
			'dependencies' => [
				'dataValues.TimeValue',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.Container',
				'jquery.valueview.ExpertExtender.Listrotator',
				'jquery.valueview.ExpertExtender.Preview',
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			],
			'messages' => [
				'valueview-expert-timeinput-calendar',
				'valueview-expert-timeinput-precision',
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
			],
		],

		'jquery.valueview.experts.UnDeserializableValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/UnDeserializableValue.js'
			],
			'dependencies' => [
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			]
		],

		'jquery.valueview.experts.UnsupportedValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/UnsupportedValue.js',
			],
			'styles' => [
				'experts/UnsupportedValue.css',
			],
			'dependencies' => [
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			],
			'messages' => [
				'valueview-expert-unsupportedvalue-unsupporteddatatype',
				'valueview-expert-unsupportedvalue-unsupporteddatavalue',
			]
		],

		'jquery.valueview.ExpertExtender' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.js',
			],
			'dependencies' => [
				'jquery.ui.inputextender',
				'jquery.valueview',
				'util.Extendable',
			],
		],

		'jquery.valueview.ExpertExtender.Container' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.Container.js',
			],
			'dependencies' => [
				'jquery.valueview.ExpertExtender',
			],
		],

		'jquery.valueview.ExpertExtender.LanguageSelector' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.LanguageSelector.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.languagesuggester',
				'jquery.valueview.ExpertExtender',
				'util.PrefixingMessageProvider',
			],
			'messages' => [
				'valueview-expertextender-languageselector-languagetemplate',
				'valueview-expertextender-languageselector-label',
			]
		],

		'jquery.valueview.ExpertExtender.Listrotator' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.Listrotator.js',
			],
			'dependencies' => [
				'jquery.ui.listrotator',
				'jquery.valueview.ExpertExtender',
			],
		],

		'jquery.valueview.ExpertExtender.Preview' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.Preview.js',
			],
			'styles' => [
				'ExpertExtender/ExpertExtender.Preview.css',
			],
			'dependencies' => [
				'jquery.ui.preview',
				'jquery.valueview.ExpertExtender',
				'util.PrefixingMessageProvider',
			],
			'messages' => [
				'valueview-preview-label',
				'valueview-preview-novalue',
			],
		],

		'jquery.valueview.ExpertExtender.UnitSelector' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.UnitSelector.js',
			],
			'dependencies' => [
				'jquery.valueview.ExpertExtender',
				'jquery.ui.unitsuggester',
			],
			'messages' => [
				'valueview-expertextender-unitsuggester-label',
			],
		],

		'wikibase.termbox' => $wikibaseTermboxPaths + [
			'class' => TermboxModule::class,
			'scripts' => [
				'dist/wikibase.termbox.main.js',
			],
			'targets' => 'mobile',
			'dependencies' => [
				'wikibase.termbox.styles',
				'wikibase.getLanguageNameByCode',
				'wikibase.entityPage.entityLoaded',
				'wikibase.WikibaseContentLanguages',
				'wikibase.getUserLanguages',
				'mw.config.values.wbRepo'
			],
			// 'messages' are declared by ./resources.json via TermboxModule.
		],

		'wikibase.termbox.styles' => $wikibaseTermboxPaths + [
			'styles' => [
				'../../resources/wikibase/termbox/main.less',
				'dist/wikibase.termbox.main.css',
			],
			'targets' => 'mobile'
		],
	];
	return $modules;
} );
