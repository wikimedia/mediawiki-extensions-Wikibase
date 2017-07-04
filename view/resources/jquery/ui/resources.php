<?php

/**
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [

		'jquery.ui.closeable' => $moduleTemplate + [
			'scripts' => [
				'jquery.ui.closeable.js',
			],
			'styles' => [
				'jquery.ui.closeable.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		'jquery.ui.tagadata' => $moduleTemplate + [
			'scripts' => [
				'jquery.ui.tagadata.js',
			],
			'styles' => [
				'jquery.ui.tagadata.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'jquery.ui.EditableTemplatedWidget.js',
			],
			'dependencies' => [
				'jquery.ui.closeable',
				'jquery.ui.TemplatedWidget',
				'util.inherit',
			],
		],

		'jquery.ui.TemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'jquery.ui.TemplatedWidget.js',
			],
			'dependencies' => [
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
			],
		],

	];

	return $modules;
} );
