<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$mwVpResources = array(
		'mw.ext.valueParsers' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueParsers.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueParsers',
				'valueParsers.parsers',
				'valueParsers.factory',
				'mw.ext.valueView',
			),
		),
	);

	// Return ValueParser's native resources plus those required by the MW extension:
	return $mwVpResources + include( __DIR__ . '/resources.php' );
} );
