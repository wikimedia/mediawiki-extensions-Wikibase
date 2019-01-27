<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/jquery',
	];

	$modules = [

		'jquery.removeClassByRegex' => $moduleTemplate + [
			'scripts' => [
				'jquery.removeClassByRegex.js',
			],
		],

		'jquery.sticknode' => $moduleTemplate + [
			'scripts' => [
				'jquery.sticknode.js',
			],
			'dependencies' => [
				'jquery.util.EventSingletonManager',
			],
		],

		'jquery.util.EventSingletonManager' => $moduleTemplate + [
			'scripts' => [
				'jquery.util.EventSingletonManager.js',
			],
			'dependencies' => [
				'jquery.throttle-debounce',
			],
		],

		'jquery.ui.closeable' => $moduleTemplate + [
			'scripts' => [
				'ui/jquery.ui.closeable.js',
			],
			'styles' => [
				'ui/jquery.ui.closeable.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		'jquery.ui.tagadata' => $moduleTemplate + [
			'scripts' => [
				'ui/jquery.ui.tagadata.js',
			],
			'styles' => [
				'ui/jquery.ui.tagadata.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'ui/jquery.ui.EditableTemplatedWidget.js',
			],
			'dependencies' => [
				'jquery.ui.closeable',
				'jquery.ui.TemplatedWidget',
				'util.inherit',
			],
		],

		'jquery.ui.TemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'ui/jquery.ui.TemplatedWidget.js',
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
