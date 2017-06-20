<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function () {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'wikibase.api.RepoApi.tests' => $moduleTemplate + [
			'scripts' => [
				'RepoApi.tests.js',
			],
			'dependencies' => [
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.api.RepoApi',
			],
		],

		'wikibase.api.RepoApiError.tests' => $moduleTemplate + [
			'scripts' => [
				'RepoApiError.tests.js',
			],
			'dependencies' => [
				'wikibase.api.RepoApiError',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-remove-timeout',
			],
		],
	];
} );
