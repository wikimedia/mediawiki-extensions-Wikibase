<?php

/**
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'jquery.ui.closeable' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui.closeable.js',
			),
			'styles' => array(
				'jquery.ui.closeable.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
			),
		),

		'jquery.ui.tagadata' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui.tagadata.js',
			),
			'styles' => array(
				'jquery.ui.tagadata.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui.EditableTemplatedWidget.js',
			),
			'dependencies' => array(
				'jquery.ui.closeable',
				'jquery.ui.TemplatedWidget',
				'util.inherit',
			),
		),

		'jquery.ui.TemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui.TemplatedWidget.js',
			),
			'dependencies' => array(
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
			),
		),

	);

	return $modules;
} );
