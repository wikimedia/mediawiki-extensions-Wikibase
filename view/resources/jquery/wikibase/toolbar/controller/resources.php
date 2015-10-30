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

		'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementgrouplistview-statementgroupview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/addtoolbar/statementgrouplistview-statementgroupview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.statementgrouplistview',
				'jquery.wikibase.toolbarcontroller',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementlistview-statementview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/addtoolbar/statementlistview-statementview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.listview',
				'jquery.wikibase.statementlistview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.referenceview-snakview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/addtoolbar/referenceview-snakview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.toolbarcontroller',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-referenceview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/addtoolbar/statementview-referenceview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.listview',
				'jquery.wikibase.statementview',
				'jquery.wikibase.toolbarcontroller',
			),
			'messages' => array(
				'wikibase-addreference',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.addtoolbar.statementview-snakview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/addtoolbar/statementview-snakview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.listview',
				'jquery.wikibase.statementview',
				'jquery.wikibase.toolbarcontroller',
			),
			'messages' => array(
				'wikibase-addqualifier',
			),
		),

		'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.entitytermsview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/edittoolbar/entitytermsview.js',
			),
			'dependencies' => array(
				'jquery.sticknode',
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.toolbarcontroller',
				'mediawiki.user',
			),
		),

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

		'jquery.wikibase.toolbarcontroller.definitions.edittoolbar.sitelinkgroupview' => $moduleTemplate + array(
			'scripts' => array(
				'definitions/edittoolbar/sitelinkgroupview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.sitelinkgroupview',
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
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.removetoolbar',
			),
		),

	);

	return $modules;
} );
