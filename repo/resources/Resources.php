<?php
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
	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
		'position' => 'top' // reducing the time between DOM construction and JS initialisation
	);

	$modules = array(
		'wikibase.ui.entityViewInit' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.entityViewInit.js' // should probably be adjusted for more modularity
			),
			'dependencies' => array(
				'mediawiki.user',
				'mw.config.values.wbRepo',
				'jquery.wikibase.entityview',
				'jquery.wikibase.toolbarcontroller',
				'jquery.wikibase.wbtooltip',
				'jquery.cookie',
				'jquery.wikibase.claimgrouplabelscroll',
				'jquery.wikibase.sitelinkgroupview',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.dataTypes',
				'wikibase.entityChangers.EntityChangersFactory',
				'wikibase.experts',
				'wikibase.formatters.getStore',
				'wikibase.EntityInitializer',
				'wikibase.parsers.getStore',
				'wikibase.RepoApi',
				'wikibase.serialization.entities',
				'wikibase.sites',
				'wikibase.store.ApiEntityStore',
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.FetchedContentUnserializer',
				'wikibase.store.MwConfigEntityStore',
				'wikibase.ValueViewBuilder'
			),
			'messages' => array(
				'pagetitle',
				'wikibase-statements',
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
				'wikibase.datamodel',
				'wikibase.serialization',
				// FIXME: Resolve implicitly required wikibase.serialization.entities dependency.
				// wikibase.serialization.entities self-registers to the SerializerFactory provided
				// by wikibase.serialization which is why wikibase.serialization.entities is
				// implicitly required as dependency.
				'wikibase.serialization.entities',
			),
		),

		'wikibase.ui.entitysearch' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.entitysearch.js',
			),
			'styles' => array(
				'themes/default/wikibase.ui.entitysearch.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
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
		$modules['wikibase.initTermBox']['dependencies'][] = 'ext.uls.displaysettings';
		$modules['wikibase.initTermBox']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.special.itemDisambiguation']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.special.entitiesWithout']['dependencies'][] = 'ext.uls.mediawiki';
	}

	return $modules;
} );
// @codeCoverageIgnoreEnd
