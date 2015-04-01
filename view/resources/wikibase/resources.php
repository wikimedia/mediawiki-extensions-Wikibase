<?php

/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'wikibase.ValueViewBuilder' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.js',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.valueview',
			),
		),

	);

	return $modules;
} );
