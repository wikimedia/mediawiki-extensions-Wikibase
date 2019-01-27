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
		'remoteExtPath' => 'Wikibase/view/tests/qunit/jquery/wikibase/toolbar',
	];

	$resources = [

		'jquery.wikibase.changetoolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.addtoolbar.tests.js',
				'jquery.wikibase.edittoolbar.tests.js',
				'jquery.wikibase.removetoolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.changetoolbar',
			],
		],

		'jquery.wikibase.singlebuttontoolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.singlebuttontoolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.singlebuttontoolbar',
			],
		],

		'jquery.wikibase.toolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			],
		],

		'jquery.wikibase.toolbarbutton.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbarbutton.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbarbutton',
			],
		],

		'jquery.wikibase.toolbaritem.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.toolbaritem.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbaritem',
			],
		],

	];

	return $resources;
} );
