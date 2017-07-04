<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	$resources = [

		'jquery.wikibase.addtoolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.addtoolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.addtoolbar',
			],
		],

		'jquery.wikibase.edittoolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.edittoolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.edittoolbar',
			],
		],

		'jquery.wikibase.removetoolbar.tests' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.removetoolbar.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.removetoolbar',
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
