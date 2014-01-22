<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
	);

	return array(

		'valueFormatters.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ValueFormatter.tests.js',
			),
			'dependencies' => array(
				'valueFormatters',
				'valueFormatters.ValueFormatter',
			),
		),

		'valueFormatters.factory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ValueFormatterFactory.tests.js',
			),
			'dependencies' => array(
				'qunit.parameterize',
				'valueFormatters.factory',
				'valueFormatters.formatters',
			),
		),

		'valueFormatters.formatters.tests' => $moduleTemplate + array(
			'scripts' => array(
				'formatters/NullFormatter.tests.js',
				'formatters/StringFormatter.tests.js',
			),
			'dependencies' => array(
				'valueFormatters.tests',
				'util.inherit',
				'valueFormatters.formatters',
			),
		),

	);

} );
