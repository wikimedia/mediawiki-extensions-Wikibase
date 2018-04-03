<?php

/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../wikibase-data-values-value-view/tests/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/tests/src',
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
