<?php

use Wikibase\View\Module\TemplateModule;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/wikibase',
	];

	$modules = [

		// common styles independent from JavaScript being enabled or disabled
		'wikibase.common' => $moduleTemplate + [
			'styles' => [
				// Order must be hierarchical, do not order alphabetically
				'wikibase.less',
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
			]
		],

		'wikibase.mobile' => $moduleTemplate + [
			'styles' => [
				'wikibase.mobile.css'
			],
			'dependencies' => [
				'jquery.wikibase.statementview.RankSelector.styles',
			],
			'targets' => 'mobile'
		],

		'wikibase.RevisionStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase.RevisionStore.js',
			],
			'dependencies' => [
				'wikibase'
			]
		],

		'wikibase.templates' => $moduleTemplate + [
			'class' => TemplateModule::class,
			'scripts' => 'templates.js',
			'dependencies' => [
				'jquery.getAttrs'
			]
		],

		'wikibase.ValueViewBuilder' => $moduleTemplate + [
			'scripts' => [
				'wikibase.ValueViewBuilder.js',
			],
			'dependencies' => [
				'wikibase',
				'jquery.valueview',
			],
		],

		'wikibase.ValueFormatterFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase.ValueFormatterFactory.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase',
			],
		],

	];

	return $modules;
} );
