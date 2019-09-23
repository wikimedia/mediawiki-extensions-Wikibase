<?php

use Wikibase\Lib\Modules\DataTypesModule;
use Wikibase\Lib\Modules\MediaWikiConfigModule;
use Wikibase\Repo\WikibaseRepo;

/**
 * Wikibase Repo ResourceLoader modules
 *
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/repo/resources',
	];

	$modules = [
		'mw.config.values.wbDataTypes' => $moduleTemplate + [
			'class' => DataTypesModule::class,
			'datatypefactory' => function() {
				return WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
			},
			'datatypesconfigvarname' => 'wbDataTypes',
		],

		'mw.config.values.wbEntityTypes' => $moduleTemplate + [
			'class' => MediaWikiConfigModule::class,
			'getconfigvalueprovider' => function() {
				return WikibaseRepo::getDefaultInstance()->getEntityTypesConfigValueProvider();
			}
		],

		'mw.config.values.wbGeoShapeStorageApiEndpoint' => $moduleTemplate + [
			'class' => MediaWikiConfigModule::class,
			'getconfigvalueprovider' => function () {
				return WikibaseRepo::getDefaultInstance()->getSettingsValueProvider(
					'wbGeoShapeStorageApiEndpoint',
					'geoShapeStorageApiEndpointUrl'
				);
			},
		],

		// Temporary, see: T199197
		'mw.config.values.wbRefTabsEnabled' => $moduleTemplate + [
			'class' => MediaWikiConfigModule::class,
			'getconfigvalueprovider' => function () {
				return WikibaseRepo::getDefaultInstance()->getSettingsValueProvider(
					'wbRefTabsEnabled',
					'enableRefTabs'
				);
			},
		],

		'wikibase.entityPage.entityLoaded' => $moduleTemplate + [
			'scripts' => [
				'wikibase.entityPage.entityLoaded.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
			'dependencies' => [
				'wikibase',
				'mediawiki.Uri',
			],
		],

		'wikibase.EntityInitializer' => $moduleTemplate + [
			'scripts' => [
				'wikibase.EntityInitializer.js'
			],
			'dependencies' => [
				'wikibase',
				'wikibase.serialization.EntityDeserializer'
			]
		],

		'wikibase.getUserLanguages' => $moduleTemplate + [
			'scripts' => [
				'wikibase.getUserLanguages.js'
			],
			'dependencies' => [
				'wikibase',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.ui.entityViewInit' => [
			'packageFiles' => [
				'repo/resources/wikibase.ui.entityViewInit.js',

				'repo/resources/experts/getStore.js',
				'repo/resources/dataTypes/wikibase.dataTypeStore.js',
				'repo/resources/dataTypes/DataTypeStore.js',
				'repo/resources/dataTypes/DataType.js',
				'repo/resources/parsers/getStore.js',
				'repo/resources/parsers/getApiBasedValueParserConstructor.js',
				'repo/resources/formatters/ApiValueFormatterFactory.js',
				'view/resources/wikibase/view/ViewFactoryFactory.js',
				'view/resources/wikibase/wikibase.RevisionStore.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.cookie',
				'mediawiki.notify',
				'mediawiki.page.watch.ajax',
				'mediawiki.Uri',
				'mediawiki.user',
				'mw.config.values.wbEntityTypes',
				'mw.config.values.wbRepo',
				'mw.config.values.wbGeoShapeStorageApiEndpoint',
				'mw.config.values.wbDataTypes',
				'jquery.wikibase.wbtooltip',
				'wikibase',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.api.FormatValueCaller',
				'wikibase.formatters.ApiValueFormatter',
				'wikibase.ValueFormatterFactory',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.EntityId',
				'wikibase.entityPage.entityLoaded',
				'wikibase.entityChangers.EntityChangersFactory',
				'wikibase.EntityInitializer',
				'wikibase.entityIdFormatter',
				'wikibase.api.RepoApi',
				'wikibase.sites',
				'wikibase.store.EntityStore',
				'wikibase.view.StructureEditorFactory',
				'wikibase.view.ToolbarFactory',
				'wikibase.WikibaseContentLanguages',
				'wikibase.getUserLanguages',
				'wikibase.experts.__namespace',
				'wikibase.experts.modules',
				'wikibase.view.__namespace',
				'wikibase.view.ReadModeViewFactory',
				'wikibase.view.ControllerViewFactory',
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.UnDeserializableValue',
				'jquery.valueview.experts.UnsupportedValue',
				'dataValues',
				'dataValues.values',
				'valueParsers.parsers',
				'valueParsers.ValueParserStore',
				'wikibase.api.ParseValueCaller',
			],
			'messages' => [
				'pagetitle',
				'wikibase-copyrighttooltip-acknowledge',
				'wikibase-anonymouseditwarning',
				'wikibase-entity-item',
				'wikibase-entity-property',
			],
			'localBasePath' => dirname( dirname( __DIR__ ) ),
			'remoteExtPath' => 'Wikibase',
		],

		'wikibase.ui.entitysearch' => $moduleTemplate + [
			'packageFiles' => [
				'wikibase.ui.entitysearch.js',

				'jquery.wikibase/jquery.wikibase.entitysearch.js',
			],
			'styles' => [
				'jquery.wikibase/themes/default/jquery.wikibase.entitysearch.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.spinner',
				'jquery.ui.ooMenu',
				'jquery.wikibase.entityselector',
			],
			'messages' => [
				'searchsuggest-containing',
			]
		],

		/* Wikibase special pages */

		'wikibase.special.newEntity' => $moduleTemplate + [
			'scripts' => [
				'wikibase.special/wikibase.special.newEntity.js',
			]
		],

		'wikibase.special.mergeItems' => $moduleTemplate + [
			'scripts' => [
				'wikibase.special/wikibase.special.mergeItems.js',
			]
		],

		'wikibase.experts.modules' => $moduleTemplate + [
				'factory' => function () {
					return WikibaseRepo::getDefaultInstance()->getPropertyValueExpertsModule();
				}
		],

	];

	return array_merge(
		$modules,
		require __DIR__ . '/experts/resources.php',
		require __DIR__ . '/formatters/resources.php'
	);
} );
