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

		'wikibase.ui.entityViewInit' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.entityViewInit.js' // should probably be adjusted for more modularity
			),
			'dependencies' => array(
				'mediawiki.page.watch.ajax',
				'mediawiki.user',
				'mw.config.values.wbRepo',
				'jquery.wikibase.toolbarcontroller',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementgrouplistview-statementgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementlistview-statementview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.entitytermsview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.referenceview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.sitelinkgroupview',
				'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.statementview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview-snakview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.sitelinkgroupview-sitelinkview',
				'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.statementview-referenceview',
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
				'wikibase.store.CachingEntityStore',
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.FetchedContentUnserializer',
				'wikibase.view.ViewFactory',
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

		'wikibase.WikibaseContentLanguages' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.WikibaseContentLanguages.js',
			),
			'dependencies' => array(
				'util.ContentLanguages',
				'util.inherit',
				'wikibase',
			),
		),

	);

	if ( defined( 'ULS_VERSION' ) ) {
		$modules['wikibase.special.itemDisambiguation']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.special.entitiesWithout']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.WikibaseContentLanguages']['dependencies'][] = 'ext.uls.languagenames';
	}

	return array_merge(
		$modules,
		include( __DIR__ . '/experts/resources.php' ),
		include( __DIR__ . '/formatters/resources.php' ),
		include( __DIR__ . '/parsers/resources.php' )
	);
} );
