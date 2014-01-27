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
		'remoteExtPath' =>  $remoteExtPathParts[1],
	);

	$mwVfResources = array(
		'mw.ext.valueFormatters' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueFormatters.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueFormatters',
				'valueFormatters.formatters',
				'valueFormatters.factory',
				'mw.ext.valueView',
			),
		),
	);

	// Return ValueFormatter's native resources plus those required by the MW extension:
	return $mwVfResources + include( __DIR__ . '/resources.php' );
} );
