<?php
/**
 * Definition of "ValueFormatters" ResourceLoader modules to be used when run as MediaWiki
 * extension.
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/src/ValueFormatters/',
		'remoteExtPath' =>  '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ) . '/src/ValueFormatters',
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
	return $mwVfResources + include( __DIR__ . '/ValueFormatters.resources.php' );
} );
