<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/resources/deprecated',
	];

	return [
		'wikibase.RepoApi' => $moduleTemplate + [
			'scripts' => [
				'wikibase.RepoApi.js',
			],
			'dependencies' => [
				'wikibase',
				'wikibase.api.RepoApi',
			],
		],

		'wikibase.RepoApiError' => $moduleTemplate + [
			'scripts' => [
				'wikibase.RepoApiError.js',
			],
			'dependencies' => [
				'wikibase',
				'wikibase.api.RepoApiError',
			],
		],
	];
} );
