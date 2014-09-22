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

	$modules = array(

		'jquery.wikibase.addtoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.addtoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.singlebuttontoolbar',
			),
			'messages' => array(
				'wikibase-add',
			),
		),

		'jquery.wikibase.edittoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.edittoolbar.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.edittoolbar.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
				'jquery.wikibase.wbtooltip',
			),
			'messages' => array(
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-remove',
				'wikibase-remove-inprogress',
				'wikibase-save',
				'wikibase-save-inprogress',
			),
		),

		'jquery.wikibase.movetoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.movetoolbar.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.movetoolbar.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			),
			'messages' => array(
				'wikibase-move-up',
				'wikibase-move-down',
			),
		),

		'jquery.wikibase.removetoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.removetoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.singlebuttontoolbar',
			),
			'messages' => array(
				'wikibase-remove',
			),
		),

		'jquery.wikibase.singlebuttontoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.singlebuttontoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			),
		),

		'jquery.wikibase.toolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbar.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.toolbar.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbaritem',
			),
		),

		'jquery.wikibase.toolbarbutton' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbarbutton.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.toolbarbutton.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbaritem',
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
				'jquery.wikibase.movetoolbar',
				'jquery.wikibase.removetoolbar',
			),
		),

		'jquery.wikibase.toolbaritem' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbaritem.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.toolbaritem.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
			),
		),

	);

	return $modules;
} );
