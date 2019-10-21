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
	$wikibaseDatavaluesValueviewPaths = [
		'localBasePath' => __DIR__ . '/wikibase-data-values-value-view',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view',
	];

	$wikibaseTermboxPaths = [
		'localBasePath' => __DIR__ . '/wikibase-termbox',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-termbox',
	];

	$modules = [
		'wikibase.datamodel' => $wikibaseDatamodelPaths + [
			'packageFiles' => [
				'index.js',

				'List.js',
				'Group.js',
				'Map.js',
				'Set.js',
				'GroupableCollection.js',
				'FingerprintableEntity.js',
				'Claim.js',
				'Entity.js',
				'EntityId.js',
				'Fingerprint.js',
				'Item.js',
				'MultiTerm.js',
				'MultiTermMap.js',
				'Property.js',
				'PropertyNoValueSnak.js',
				'PropertySomeValueSnak.js',
				'PropertyValueSnak.js',
				'Reference.js',
				'ReferenceList.js',
				'SiteLink.js',
				'SiteLinkSet.js',
				'Snak.js',
				'SnakList.js',
				'Statement.js',
				'StatementGroup.js',
				'StatementGroupSet.js',
				'StatementList.js',
				'Term.js',
				'TermMap.js',
			],
			'dependencies' => [
				'util.inherit',
				'dataValues.DataValue',
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
			'packageFiles' => [
				'jquery/jquery.animateWithEvent.js',
				'jquery/jquery.AnimationEvent.js',
				'jquery/jquery.PurposedCallbacks.js',
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

		'jquery.ui.commonssuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.commonssuggester.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.commonssuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
				'util.highlightSubstring',
			],
		],

		'jquery.ui.languagesuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.languagesuggester.js',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
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
		'wikibase.serialization' => $wikibaseSerializationPaths + [
			'packageFiles' => [
				'index.js',

				'Deserializers/Deserializer.js',
				'Deserializers/SnakDeserializer.js',
				'Deserializers/StatementGroupSetDeserializer.js',
				'Deserializers/StatementGroupDeserializer.js',
				'Deserializers/StatementListDeserializer.js',
				'Deserializers/StatementDeserializer.js',
				'Deserializers/ClaimDeserializer.js',
				'Deserializers/TermDeserializer.js',
				'Serializers/ClaimSerializer.js',
				'Deserializers/ReferenceListDeserializer.js',
				'Deserializers/ReferenceDeserializer.js',
				'Deserializers/SnakListDeserializer.js',
				'Serializers/TermMapSerializer.js',
				'Serializers/TermSerializer.js',
				'Deserializers/TermMapDeserializer.js',
				'Deserializers/EntityDeserializer.js',
				'StrategyProvider.js',
				'Deserializers/ItemDeserializer.js',
				'Deserializers/PropertyDeserializer.js',
				'Deserializers/SiteLinkSetDeserializer.js',
				'Deserializers/SiteLinkDeserializer.js',
				'Deserializers/FingerprintDeserializer.js',
				'Deserializers/MultiTermMapDeserializer.js',
				'Deserializers/MultiTermDeserializer.js',
				'Serializers/StatementSerializer.js',
				'Serializers/StatementListSerializer.js',
				'Serializers/ReferenceListSerializer.js',
				'Serializers/ReferenceSerializer.js',
				'Serializers/Serializer.js',
				'Serializers/SnakListSerializer.js',
				'Serializers/SnakSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel',
				'dataValues',
				'dataValues.values',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],
		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without losing any properties.
		'jquery.valueview' => $wikibaseDatavaluesValueviewSrcPaths + [
			'packageFiles' => [
				'jquery.valueview.js',
				'jquery.valueview.valueview.js',
				'jquery.valueview.ViewState.js',
			],
			'styles' => [
				'jquery.valueview.valueview.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'jquery.ui',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.EmptyValue',
				'jquery.valueview.experts.UnsupportedValue',
				'util.Notifier',
				'valueFormatters',
				'valueParsers.ValueParserStore',
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

		'jquery.valueview.experts.QuantityInput' => $wikibaseDatavaluesValueviewPaths + [
			'packageFiles' => [
				'src/experts/QuantityInput.js',
				'src/ExpertExtender/ExpertExtender.UnitSelector.js',
				'lib/jquery.ui/jquery.ui.unitsuggester.js',
			],
			'styles' => [
				'lib/jquery.ui/jquery.ui.unitsuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
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
				'ExpertExtender/ExpertExtender.Container.js',
				'ExpertExtender/ExpertExtender.Listrotator.js',
				'../lib/jquery.ui/jquery.ui.listrotator.js',
				'ExpertExtender/ExpertExtender.Preview.js',
				'../lib/jquery.ui/jquery.ui.preview.js',
			],
			'styles' => [
				'../lib/jquery.ui/jquery.ui.inputextender.css',
				'../lib/jquery.ui/jquery.ui.listrotator.css',
				'ExpertExtender/ExpertExtender.Preview.css',
				'../lib/jquery.ui/jquery.ui.preview.css',
			],
			'dependencies' => [
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.valueview',
				'util.Extendable',
				'jquery.ui',
				'util.CombiningMessageProvider',
				'util.HashMessageProvider',
				'util.PrefixingMessageProvider',
			],
			'messages' => [
				'valueview-listrotator-manually',
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
		'wikibase.tainted-ref.common' => [
			'factory' => function () {
				return new ResourceLoaderFileModule(
					[
						'scripts' => [
							'tainted-ref.common.js',
						],
						'styles' => [
							'tainted-ref.app.css',
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
		'wikibase.tainted-ref.init' => [
			'factory' => function () {
				return new ResourceLoaderFileModule(
					[
						'scripts' => [
							'tainted-ref.init.js',
						],
						'styles' => [
							'tainted-ref.app.css',
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
