<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/jquery/ui',
	];

	return [
		'jquery.ui.closeable.tests' => $moduleBase + [
			'scripts' => [
				'jquery.ui.closeable.tests.js',
			],
			'dependencies' => [
				'jquery.ui.closeable',
			],
		],

		'jquery.ui.tagadata.tests' => $moduleBase + [
			'scripts' => [
				'jquery.ui.tagadata.tests.js',
			],
			'dependencies' => [
				'jquery.ui.tagadata',
			],
		],

		'jquery.ui.EditableTemplatedWidget.tests' => $moduleBase + [
			'scripts' => [
				'jquery.ui.EditableTemplatedWidget.tests.js',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'wikibase.templates',
			],
		],

		'jquery.ui.TemplatedWidget.tests' => $moduleBase + [
			'scripts' => [
				'jquery.ui.TemplatedWidget.tests.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'wikibase.templates',
			],
		],
	];
} );
