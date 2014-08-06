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

		'jquery.wikibase.movetoolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'movetoolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.movetoolbar',
				'jquery.wikibase.listview',
			),
		),

		'jquery.wikibase.toolbar.tests' => $moduleTemplate + array(
			'scripts' => array(
				'toolbar.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
				'jquery.wikibase.toolbarlabel',
				'wikibase.utilities',
			),
		),

		'jquery.wikibase.toolbarbase.tests' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarbase.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbase',
			),
		),

		'jquery.wikibase.toolbarbutton.tests' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarbutton.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbutton',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.toolbareditgroup.tests' => $moduleTemplate + array(
			'scripts' => array(
				'toolbareditgroup.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
				'jquery.wikibase.toolbareditgroup',
			),
		),

		'jquery.wikibase.toolbarlabel.tests' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarlabel.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarlabel',
			),
		),

	);

	return $resources;
} );
