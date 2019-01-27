<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/jquery/wikibase/toolbar',
	];

	$modules = [

		'jquery.wikibase.changetoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.addtoolbar.js',
				'jquery.wikibase.edittoolbar.js',
				'jquery.wikibase.removetoolbar.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.edittoolbar.css',
			],
			'dependencies' => [
				'jquery.wikibase.singlebuttontoolbar',
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
				'jquery.wikibase.wbtooltip',
				'wikibase.api.RepoApiError',
			],
			'messages' => [
				'wikibase-add',
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-remove',
				'wikibase-remove-inprogress',
				'wikibase-save',
				'wikibase-publish',
			],
		],

		'jquery.wikibase.singlebuttontoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.singlebuttontoolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			],
		],

		'jquery.wikibase.toolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbar.styles',
			],
		],

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + [
			'styles' => [
				'themes/default/jquery.wikibase.toolbar.css',
			],
		],

		'jquery.wikibase.toolbarbutton' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbarbutton.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbarbutton.styles',
			],
		],

		'jquery.wikibase.toolbarbutton.styles' => $moduleTemplate + [
			'styles' => [
				'themes/default/jquery.wikibase.toolbarbutton.css',
			],
		],

		'jquery.wikibase.toolbaritem' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbaritem.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.toolbaritem.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

	];

	return $modules;
} );
