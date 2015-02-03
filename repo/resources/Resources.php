<?php

use Wikibase\Repo\WikibaseRepo;

/**
 * Wikibase Repo ResourceLoader modules
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
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

		'jquery.ui.closeable' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.closeable.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.closeable.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
			),
		),

		'jquery.ui.tagadata' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.tagadata.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.tagadata.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.effects.blind',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.EditableTemplatedWidget.js',
			),
			'dependencies' => array(
				'jquery.ui.closeable',
				'jquery.ui.TemplatedWidget',
				'util.inherit',
			),
		),

		'jquery.ui.TemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.TemplatedWidget.js',
			),
			'dependencies' => array(
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
			),
		),

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
			'class' => 'DataTypes\DataTypesModule',
			'datatypefactory' => function() {
				return WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
			},
			'datatypesconfigvarname' => 'wbDataTypes',
		),

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

		'wikibase.getLanguageNameByCode' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.getLanguageNameByCode.js'
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.RevisionStore' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RevisionStore.js',
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.templates' => $moduleTemplate + array(
			'class' => 'Wikibase\TemplateModule',
			'scripts' => 'templates.js',
		),

		'wikibase.ValueViewBuilder' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.js',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.valueview',
			),
		),

		'wikibase.ui.entityViewInit' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.entityViewInit.js' // should probably be adjusted for more modularity
			),
			'dependencies' => array(
				'mediawiki.user',
				'mw.config.values.wbRepo',
				'jquery.wikibase.itemview',
				'jquery.wikibase.propertyview',
				'jquery.wikibase.toolbarcontroller',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementgrouplistview-statementgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementlistview-statementview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.aliasesview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.descriptionview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.entitytermsview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.labelview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.sitelinkgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.statementview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.sitelinkgroupview-sitelinkview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.statementview-snakview',
				'jquery.wikibase.wbtooltip',
				'jquery.cookie',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.dataTypeStore',
				'wikibase.entityChangers.EntityChangersFactory',
				'wikibase.experts.getStore',
				'wikibase.formatters.getStore',
				'wikibase.EntityInitializer',
				'wikibase.parsers.getStore',
				'wikibase.api.RepoApi',
				'wikibase.RevisionStore',
				'wikibase.serialization.EntityDeserializer',
				'wikibase.sites',
				'wikibase.store.ApiEntityStore',
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.FetchedContentUnserializer',
				'wikibase.store.MwConfigEntityStore',
				'wikibase.ValueViewBuilder'
			),
			'messages' => array(
				'pagetitle',
				'wikibase-copyrighttooltip-acknowledge',
				'wikibase-anonymouseditwarning',
				'wikibase-entity-item',
				'wikibase-entity-property',
				'wikibase-restrictionedit-tooltip-message',
				'wikibase-blockeduser-tooltip-message',
			)
		),

		'wikibase.EntityInitializer' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.EntityInitializer.js',
			),
			'dependencies' => array(
				'json',
				'wikibase',
				'wikibase.datamodel.Entity',
				'wikibase.serialization.EntityDeserializer',
			),
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
			'styles' => array(
				'wikibase.special/wikibase.special.css'
			),
			'dependencies' => array(
				'wikibase'
			)
		),

		'wikibase.special.entitiesWithout' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.special/wikibase.special.entitiesWithout.js'
			),
			'dependencies' => array(
				'wikibase.special',
				'jquery.ui.suggester'
			)
		),

		'wikibase.special.itemByTitle' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.special/wikibase.special.itemByTitle.js'
			),
			'dependencies' => array(
				'wikibase.sites',
				'wikibase.special',
				'jquery.ui.suggester'
			)
		),

		'wikibase.special.itemDisambiguation' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.special/wikibase.special.itemDisambiguation.js'
			),
			'dependencies' => array(
				'wikibase.special',
				'jquery.ui.suggester'
			)
		),

		'wikibase.toc' => $moduleTemplate + array(
			'styles' => array(
				'themes/default/wikibase.toc.css',
			),
		),
	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.special.itemDisambiguation']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.special.entitiesWithout']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return array_merge(
		$modules,
		include( __DIR__ . '/entityChangers/resources.php' ),
		include( __DIR__ . '/experts/resources.php' ),
		include( __DIR__ . '/formatters/resources.php' ),
		include( __DIR__ . '/parsers/resources.php' ),
		include( __DIR__ . '/jquery/resources.php' ),
		include( __DIR__ . '/store/resources.php' )
	);
} );
