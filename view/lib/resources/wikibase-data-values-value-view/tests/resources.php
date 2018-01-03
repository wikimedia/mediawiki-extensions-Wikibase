<?php

/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..'
		. DIRECTORY_SEPARATOR . 'wikibase-data-values-value-view' . DIRECTORY_SEPARATOR . 'tests'
		. DIRECTORY_SEPARATOR . 'src';

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, $dir, 2
	);
	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [
		'jquery.valueview.tests.MockViewState' => $moduleTemplate + [
			'scripts' => [
				'jquery.valueview.tests.MockViewState.js',
			],
			'dependencies' => [
				'jquery.valueview.ViewState',
				'util.inherit',
			],
		],

		'jquery.valueview.tests.testExpert' => $moduleTemplate + [
			'scripts' => [
				'jquery.valueview.tests.testExpert.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.tests.MockViewState',
				'util.Notifier',
			],
		],
	];
} );
