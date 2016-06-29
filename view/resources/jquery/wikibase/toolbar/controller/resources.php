<?php
/**
 * @license GPL-2.0+
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
	);

	$modules = array(

		'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/removetoolbar/referenceview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.removetoolbar',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.toolbarcontroller',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.referenceview-snakview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/removetoolbar/referenceview-snakview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.listview',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.removetoolbar',
				'jquery.wikibase.toolbarcontroller',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.sitelinkgroupview-sitelinkview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/removetoolbar/sitelinkgroupview-sitelinkview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.removetoolbar',
				'jquery.wikibase.sitelinkgroupview',
				'jquery.wikibase.toolbarcontroller',
			),
			'messages' => array(
				'wikibase-remove',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.removetoolbar.statementview-snakview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/removetoolbar/statementview-snakview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.listview',
				'jquery.wikibase.removetoolbar',
				'jquery.wikibase.statementview',
				'jquery.wikibase.toolbarcontroller',
			),
		),

		'jquery.wikibase.toolbarcontroller' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbarcontroller.js',
				'jquery.wikibase.toolbarcontroller.definitions.js',
			),
			'dependencies' => array(
				'jquery.wikibase.removetoolbar',
			),
		),

	);

	return $modules;
} );
