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
				'wikibase.api.RepoApiError',
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
			'dependencies' => array(
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbar.styles',
			),
		),

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				'themes/default/jquery.wikibase.toolbar.css',
			),
		),

		'jquery.wikibase.toolbarbutton' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.toolbarbutton.js',
			),
			'dependencies' => array(
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbarbutton.styles',
			),
		),

		'jquery.wikibase.toolbarbutton.styles' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				'themes/default/jquery.wikibase.toolbarbutton.css',
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

	return array_merge(
		$modules,
		include __DIR__ . '/controller/resources.php'
	);
} );
