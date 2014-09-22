<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$resources = array(

		'jquery.wikibase.addtoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.addtoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
			),
		),

		'jquery.wikibase.edittoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.edittoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.edittoolbar',
			),
		),

		'jquery.wikibase.movetoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.movetoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.movetoolbar',
				'jquery.wikibase.listview',
			),
		),

		'jquery.wikibase.removetoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.removetoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.removetoolbar',
			),
		),

		'jquery.wikibase.singlebuttontoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.singlebuttontoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.singlebuttontoolbar',
			),
		),

		'jquery.wikibase.toolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			),
		),

		'jquery.wikibase.toolbarbutton.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbarbutton.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbutton',
			),
		),

		'jquery.wikibase.toolbaritem.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbaritem.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbaritem',
			),
		),

	);

	return $resources;
} );
