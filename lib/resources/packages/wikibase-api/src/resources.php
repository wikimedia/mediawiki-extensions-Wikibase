<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(

		'wikibase.api.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
			)
		),

		'wikibase.api.FormatValueCaller' => $moduleTemplate + array(
			'scripts' => array(
				'FormatValueCaller.js',
			),
			'dependencies' => array(
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.api.getLocationAgnosticMwApi' => $moduleTemplate + array(
			'scripts' => array(
				'getLocationAgnosticMwApi.js',
			),
			'dependencies' => array(
				'mediawiki.api',
				'wikibase.api.__namespace',
			),
		),

		'wikibase.api.ParseValueCaller' => $moduleTemplate + array(
			'scripts' => array(
				'ParseValueCaller.js',
			),
			'dependencies' => array(
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.api.RepoApi' => $moduleTemplate + array(
			'scripts' => array(
				'RepoApi.js',
			),
			'dependencies' => array(
				'json',
				'wikibase.api.__namespace',
			),
		),

		'wikibase.api.RepoApiError' => $moduleTemplate + array(
			'scripts' => array(
				'RepoApiError.js',
			),
			'messages' => array(
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.api.__namespace',
			),
		),

	);

} );
