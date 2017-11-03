<?php

use Wikibase\Repo\Modules\DataTypesModule;
use Wikibase\Repo\Modules\MediaWikiConfigModule;
use Wikibase\Repo\WikibaseRepo;

/**
 * Wikibase Repo ResourceLoader modules
 *
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
		'position' => 'top' // reducing the time between DOM construction and JS initialisation
	];

	$modules = [

		'jquery.wikibase.entitysearch' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase/jquery.wikibase.entitysearch.js',
			],
			'styles' => [
				'jquery.wikibase/themes/default/jquery.wikibase.entitysearch.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.ooMenu',
				'jquery.wikibase.entityselector',
			],
		],

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

		'mw.config.values.wbTabularDataStorageApiEndpoint' => $moduleTemplate + [
			'class' => MediaWikiConfigModule::class,
			'getconfigvalueprovider' => function () {
				return WikibaseRepo::getDefaultInstance()->getSettingsValueProvider(
					'wbTabularDataStorageApiEndpoint',
					'tabularDataStorageApiEndpointUrl'
				);
			},
		],

		'wikibase.dataTypes.__namespace' => $moduleTemplate + [
			'scripts' => 'dataTypes/__namespace.js',
			'dependencies' => [
				'wikibase'
			],
	   ],

		'wikibase.dataTypes.DataType' => $moduleTemplate + [
			'scripts' => 'dataTypes/DataType.js',
			'dependencies' => [
				'wikibase.dataTypes.__namespace',
			],
		],

		'wikibase.dataTypes.DataTypeStore' => $moduleTemplate + [
			'scripts' => 'dataTypes/DataTypeStore.js',
			'dependencies' => [
				'wikibase.dataTypes.__namespace',
				'wikibase.dataTypes.DataType',
			]
		],

		'wikibase.dataTypeStore' => $moduleTemplate + [
			'scripts' => [
				'dataTypes/wikibase.dataTypeStore.js',
			],
			'dependencies' => [
				'wikibase.dataTypes.DataType',
				'wikibase.dataTypes.DataTypeStore',
				'mw.config.values.wbDataTypes',
				'wikibase',
			],
		],

		'wikibase.entityPage.entityLoaded' => $moduleTemplate + [
			'scripts' => [
				'wikibase.entityPage.entityLoaded.js',
			]
		],

		'wikibase.ui.entityViewInit' => $moduleTemplate + [
			'scripts' => [
				'wikibase.EntityInitializer.js',
				'wikibase.ui.entityViewInit.js',
			],
			'dependencies' => [
				'mediawiki.api.options',
				'mediawiki.cookie',
				'mediawiki.page.watch.ajax',
				'mediawiki.user',
				'mw.config.values.wbEntityTypes',
				'mw.config.values.wbRepo',
				'mw.config.values.wbGeoShapeStorageApiEndpoint',
				'jquery.wikibase.wbtooltip',
				'wikibase',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.datamodel.Entity',
				'wikibase.datamodel.EntityId',
				'wikibase.dataTypeStore',
				'wikibase.entityChangers.EntityChangersFactory',
				'wikibase.experts.getStore',
				'wikibase.formatters.ApiValueFormatterFactory',
				'wikibase.entityIdFormatter.CachingEntityIdHtmlFormatter',
				'wikibase.entityIdFormatter.CachingEntityIdPlainFormatter',
				'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter',
				'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter',
				'wikibase.parsers.getStore',
				'wikibase.api.RepoApi',
				'wikibase.RevisionStore',
				'wikibase.serialization.EntityDeserializer',
				'wikibase.sites',
				'wikibase.store.ApiEntityStore',
				'wikibase.store.CachingEntityStore',
				'wikibase.store.CombiningEntityStore',
				'wikibase.view.ControllerViewFactory',
				'wikibase.view.ReadModeViewFactory',
				'wikibase.view.StructureEditorFactory',
				'wikibase.view.ToolbarFactory',
				'wikibase.WikibaseContentLanguages'
			],
			'messages' => [
				'pagetitle',
				'wikibase-copyrighttooltip-acknowledge',
				'wikibase-anonymouseditwarning',
				'wikibase-entity-item',
				'wikibase-entity-property',
			]
		],

		'wikibase.ui.entitysearch' => $moduleTemplate + [
			'scripts' => [
				'wikibase.ui.entitysearch.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.spinner',
				'jquery.ui.ooMenu',
				'jquery.wikibase.entitysearch',
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

		'wikibase.special' => $moduleTemplate + [
			'position' => 'top',
			'styles' => [
				'wikibase.special/wikibase.special.css'
			],
		],

		'wikibase.experts.modules' => $moduleTemplate + [
				'factory' => function () {
					return WikibaseRepo::getDefaultInstance()->getPropertyValueExpertsModule();
				}
		],

	];

	return array_merge(
		$modules,
		include __DIR__ . '/experts/resources.php',
		include __DIR__ . '/formatters/resources.php',
		include __DIR__ . '/parsers/resources.php'
	);
} );
