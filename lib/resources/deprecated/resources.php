<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(
		'wikibase.RepoApi' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RepoApi.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.RepoApi',
			),
		),

		'wikibase.RepoApiError' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.RepoApiError.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.RepoApiError',
			),
		),
	);
} );
