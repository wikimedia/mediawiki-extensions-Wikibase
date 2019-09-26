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

		'dataValues' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'dataValues.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
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

		'util.Notifier' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.Notifier.js',
			],
		],

		'util.PrefixingMessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.PrefixingMessageProvider.js',
			],
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

		'wikibase.serialization.__namespace' => $wikibaseSerializationPaths + [
				'scripts' => [
					'__namespace.js',
				],
				'dependencies' => [
					'wikibase',
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
				'packageFiles' => [
					'Deserializers/EntityDeserializer.js',

					'StrategyProvider.js',
					'Deserializers/ItemDeserializer.js',
					'Deserializers/PropertyDeserializer.js',
					'Deserializers/SiteLinkSetDeserializer.js',
					'Deserializers/SiteLinkDeserializer.js',
					'Deserializers/FingerprintDeserializer.js',
					'Deserializers/MultiTermMapDeserializer.js',
					'Deserializers/MultiTermDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.datamodel.Property',
					'wikibase.datamodel.Fingerprint',
					'wikibase.datamodel.MultiTerm',
					'wikibase.datamodel.MultiTermMap',
					'wikibase.datamodel.SiteLinkSet',
					'wikibase.datamodel.SiteLink',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.TermMapDeserializer',
					'wikibase.serialization.StatementGroupSetDeserializer',
				],
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
			'packageFiles' => [
				'Deserializers/StatementGroupSetDeserializer.js',

				'Deserializers/StatementGroupDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementGroup',
				'wikibase.serialization.StatementListDeserializer',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
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
			'packageFiles' => [
				'Deserializers/StatementDeserializer.js',

				'Deserializers/ClaimDeserializer.js',
				'Deserializers/ReferenceListDeserializer.js',
				'Deserializers/ReferenceDeserializer.js',
				'Deserializers/SnakListDeserializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.SnakList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.SnakDeserializer',
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
			'packageFiles' => [
				'Serializers/TermMapSerializer.js',
				'Serializers/TermSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.TermMap',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
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
			'packageFiles' => [
				'experts/MonolingualText.js',
				'ExpertExtender/ExpertExtender.LanguageSelector.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.event.special.eachchange',
				'jquery.ui.languagesuggester',
				'util.PrefixingMessageProvider',
			],
			'messages' => [
				'valueview-expertextender-languageselector-languagetemplate',
				'valueview-expertextender-languageselector-label',
			],
		],

		'jquery.valueview.experts.QuantityInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			'packageFiles' => [
				'experts/QuantityInput.js',
				'ExpertExtender/ExpertExtender.UnitSelector.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.ui.unitsuggester',
			],
			'messages' => [
				'valueview-expertextender-unitsuggester-label',
			],
		],

		'jquery.valueview.experts.StringValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'experts/StringValue.js',
				'../lib/jquery/jquery.focusAt.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.valueview.experts',
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
				'../lib/jquery.ui/jquery.ui.inputextender.js',
			],
			'styles' => [
				'../lib/jquery.ui/jquery.ui.inputextender.css',
			],
			'dependencies' => [
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
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

		'jquery.valueview.ExpertExtender.Listrotator' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.Listrotator.js',
				'../lib/jquery.ui/jquery.ui.listrotator.js',
			],
			'styles' => [
				'../lib/jquery.ui/jquery.ui.listrotator.css',
			],
			'dependencies' => [
				'jquery.valueview.ExpertExtender',
				'jquery.ui.autocomplete', // needs jquery.ui.menu
				'jquery.ui.widget',
				'jquery.ui.position',
			],
			'messages' => [
				'valueview-listrotator-manually',
			],
		],

		'jquery.valueview.ExpertExtender.Preview' => $wikibaseDatavaluesValueviewSrcPaths + [
			'scripts' => [
				'ExpertExtender/ExpertExtender.Preview.js',
				'../lib/jquery.ui/jquery.ui.preview.js',
			],
			'styles' => [
				'ExpertExtender/ExpertExtender.Preview.css',
				'../lib/jquery.ui/jquery.ui.preview.css',
			],
			'dependencies' => [
				'jquery.ui.widget',
				'jquery.valueview.ExpertExtender',
				'util.CombiningMessageProvider',
				'util.HashMessageProvider',
				'util.PrefixingMessageProvider',
			],
			'messages' => [
				'valueview-preview-label',
				'valueview-preview-novalue',
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
		'wikibase.tainted-ref' => [
			'factory' => function () {
				return new ResourceLoaderFileModule(
					[
						'scripts' => [
							'tainted-ref.common.js'
						],
						'styles' => [
							//'tainted-ref.app.css',
						],
						'dependencies' => [
							'vue2',
						],
						'remoteExtPath' => 'Wikibase/view/lib/wikibase-tainted-ref/dist',
					],
					__DIR__ . '/wikibase-tainted-ref/dist'
				);
			},
		],
	];
	return $modules;
} );
