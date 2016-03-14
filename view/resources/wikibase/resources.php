<?php

use Wikibase\View\Module\TemplateModule;

/**
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		// common styles independent from JavaScript being enabled or disabled
		'wikibase.common' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				// Order must be hierarchical, do not order alphabetically
				'wikibase.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'../jquery/wikibase/themes/default/jquery.wikibase.statementgroupview.css',
			)
		),

		'wikibase.mobile' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				'wikibase.mobile.css'
			),
			'dependencies' => array(
				'jquery.wikibase.statementview.RankSelector.styles'
			),
			'targets' => 'mobile'
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
			'class' => TemplateModule::class,
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

		'wikibase.ValueFormatterFactory' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueFormatterFactory.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase',
			),
		),

	);

	return $modules;
} );
