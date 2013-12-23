<?php
/**
 * Definition of "ValueParser" ResourceLoader modules to be used when run as MediaWiki extension.
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/src/ValueParsers/',
		'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ) . '/src/ValueParsers',
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
	return $mwVpResources + include( __DIR__ . '/ValueParsers.resources.php' );
} );
