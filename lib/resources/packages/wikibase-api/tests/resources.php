<?php

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(
		'wikibase.api.RepoApi.tests' => $moduleTemplate + array(
			'scripts' => array(
				'RepoApi.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.api.RepoApi',
			),
		),

		'wikibase.api.RepoApiError.tests' => $moduleTemplate + array(
			'scripts' => array(
				'RepoApiError.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.RepoApiError',
			),
			'messages' => array(
				'wikibase-error-unexpected',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-client-error',
			),
		),
	);

} );
