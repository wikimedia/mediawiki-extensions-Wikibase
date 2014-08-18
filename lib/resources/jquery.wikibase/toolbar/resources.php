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
				'addtoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbase',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-add',
			),
		),

		'jquery.wikibase.edittoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'edittoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbase',
				'jquery.wikibase.toolbareditgroup',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-remove-inprogress',
				'wikibase-save-inprogress',
			),
		),

		'jquery.wikibase.movetoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'movetoolbar.js',
			),
			'styles' => array(
				'themes/default/movetoolbar.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.wikibase.toolbarbase',
				'jquery.wikibase.toolbarbutton',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-move-up',
				'wikibase-move-down',
			),
		),

		'jquery.wikibase.removetoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'removetoolbar.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbase',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-remove',
			),
		),

		'jquery.wikibase.toolbar' => $moduleTemplate + array(
			'scripts' => array(
				'toolbar.js',
			),
			'styles' => array(
				'themes/default/toolbar.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarbutton',
			),
		),

		'jquery.wikibase.toolbarbase' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarbase.js',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbareditgroup',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.toolbarbutton' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarbutton.js',
			),
			'styles' => array(
				'themes/default/toolbarbutton.css',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbarlabel',
			),
		),

		'jquery.wikibase.toolbarcontroller' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarcontroller.js',
				'toolbarcontroller.definitions.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.movetoolbar',
				'jquery.wikibase.removetoolbar',
			),
		),

		'jquery.wikibase.toolbareditgroup' => $moduleTemplate + array(
			'scripts' => array(
				'toolbareditgroup.js',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.wikibase.toolbar',
				'jquery.wikibase.wbtooltip',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-save',
				'wikibase-remove',
			),
		),

		'jquery.wikibase.toolbarlabel' => $moduleTemplate + array(
			'scripts' => array(
				'toolbarlabel.js',
			),
			'styles' => array(
				'themes/default/toolbarlabel.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'wikibase.utilities',
			),
		),

	);

	return $modules;
} );
