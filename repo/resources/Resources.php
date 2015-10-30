<?php

use DataTypes\Modules\DataTypesModule;
use Wikibase\Repo\Modules\MediaWikiConfigModule;
use Wikibase\Repo\WikibaseRepo;

/**
 * Wikibase Repo ResourceLoader modules
 *
 * @since 0.1
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

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
		'position' => 'top' // reducing the time between DOM construction and JS initialisation
	);

	$modules = array(

		'jquery.wikibase.entitysearch' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.entitysearch.js',
			),
			'styles' => array(
				'jquery.wikibase/themes/default/jquery.wikibase.entitysearch.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.ooMenu',
				'jquery.wikibase.entityselector',
			),
		),

		'mw.config.values.wbDataTypes' => $moduleTemplate + array(
			'class' => DataTypesModule::class,
			'datatypefactory' => function() {
				return WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
			},
			'datatypesconfigvarname' => 'wbDataTypes',
		),

		'mw.config.values.wbEntityTypes' => $moduleTemplate + [
			'class' => MediaWikiConfigModule::class,
			'getconfigvalueprovider' => function() {
				return WikibaseRepo::getDefaultInstance()->getEntityTypesConfigValueProvider();
			}
		],

		'wikibase.dataTypeStore' => $moduleTemplate + array(
			'scripts' => array(
				'dataTypes/wikibase.dataTypeStore.js',
			),
			'dependencies' => array(
				'dataTypes.DataType',
				'dataTypes.DataTypeStore',
				'mw.config.values.wbDataTypes',
				'wikibase',
			),
		),

		'wikibase.ui.entityViewInit' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.EntityInitializer.js',
				'wikibase.ui.entityViewInit.js',
			),
			'dependencies' => array(
				'mediawiki.page.watch.ajax',
				'mediawiki.user',
				'mw.config.values.wbEntityTypes',
				'mw.config.values.wbRepo',
				'jquery.wikibase.toolbarcontroller',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementgrouplistview-statementgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementlistview-statementview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.entitytermsview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.sitelinkgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.sitelinkgroupview-sitelinkview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.statementview-snakview',
				'jquery.wikibase.wbtooltip',
				'jquery.cookie',
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
				'wikibase.view.ToolbarFactory',
				'wikibase.WikibaseContentLanguages'
			),
			'messages' => array(
				'pagetitle',
				'wikibase-copyrighttooltip-acknowledge',
				'wikibase-anonymouseditwarning',
				'wikibase-entity-item',
				'wikibase-entity-property',
			)
		),

		'wikibase.ui.entitysearch' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.entitysearch.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.spinner',
				'jquery.ui.ooMenu',
				'jquery.wikibase.entitysearch',
				'jquery.wikibase.entityselector',
			),
			'messages' => array(
				'searchsuggest-containing',
			)
		),

		/* Wikibase special pages */

		'wikibase.special' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				'wikibase.special/wikibase.special.css'
			),
		),

		'wikibase.special.itemByTitle' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.special/wikibase.special.itemByTitle.js'
			),
			'dependencies' => array(
				'wikibase.sites',
				'jquery.ui.suggester',
				'oojs-ui'
			)
		),

	);

	return array_merge(
		$modules,
		include __DIR__ . '/experts/resources.php',
		include __DIR__ . '/formatters/resources.php',
		include __DIR__ . '/parsers/resources.php'
	);
} );
