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
		'remoteExtPath' =>  '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
	);

	return array(

		'valueFormatters' => $moduleTemplate + array(
			'scripts' => array(
				'valueFormatters.js',
			),
		),

		'valueFormatters.factory' => $moduleTemplate + array(
			'scripts' => array(
				'ValueFormatterFactory.js',
			),
			'dependencies' => array(
				'valueFormatters',
			),
		),

		'valueFormatters.ValueFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'formatters/ValueFormatter.js',
			),
			'dependencies' => array(
				'valueFormatters',
			),
		),

		'valueFormatters.formatters' => $moduleTemplate + array(
			'scripts' => array(
				'formatters/NullFormatter.js',
				'formatters/StringFormatter.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

	);

} );
