<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
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
