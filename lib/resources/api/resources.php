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
				'wikibase' // For the namespace
			)
		),

		'wikibase.api.FormatValueCaller' => $moduleTemplate + array(
			'scripts' => array(
				'FormatValueCaller.js',
			),
			'dependencies' => array(
				'wikibase.api.__namespace',
				'wikibase.RepoApiError',
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
				'wikibase.RepoApiError',
			)
		),

	);

} );
