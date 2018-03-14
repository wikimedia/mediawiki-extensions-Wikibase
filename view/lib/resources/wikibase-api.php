<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
return call_user_func( function () {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../wikibase-api/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-api/src',
	];

	return [
		'wikibase.api.__namespace' => $moduleTemplate + [
				'scripts' => [
					'namespace.js'
				],
			],

		'wikibase.api.FormatValueCaller' => $moduleTemplate + [
				'scripts' => [
					'FormatValueCaller.js',
				],
				'dependencies' => [
					'dataValues.DataValue',
					'wikibase.api.__namespace',
					'wikibase.api.RepoApiError',
				]
			],

		'wikibase.api.getLocationAgnosticMwApi' => $moduleTemplate + [
				'scripts' => [
					'getLocationAgnosticMwApi.js',
				],
				'dependencies' => [
					'mediawiki.api',
					'mediawiki.ForeignApi',
					'wikibase.api.__namespace',
				],
			],

		'wikibase.api.ParseValueCaller' => $moduleTemplate + [
				'scripts' => [
					'ParseValueCaller.js',
				],
				'dependencies' => [
					'wikibase.api.__namespace',
					'wikibase.api.RepoApiError',
				]
			],

		'wikibase.api.RepoApi' => $moduleTemplate + [
				'scripts' => [
					'RepoApi.js',
				],
				'dependencies' => [
					'wikibase.api.__namespace',
				],
			],

		'wikibase.api.RepoApiError' => $moduleTemplate + [
				'scripts' => [
					'RepoApiError.js',
				],
				'messages' => [
					'wikibase-error-unexpected',
					'wikibase-error-save-generic',
					'wikibase-error-remove-generic',
					'wikibase-error-save-timeout',
					'wikibase-error-remove-timeout',
					'wikibase-error-ui-no-external-page',
					'wikibase-error-ui-edit-conflict',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.api.__namespace',
				],
			],
	];
} );
