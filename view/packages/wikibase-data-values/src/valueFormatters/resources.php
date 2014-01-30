<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' =>  $remoteExtPathParts[1],
	);

	return array(

		'mw.ext.valueFormatters' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueFormatters.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueFormatters',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterFactory',
				'mw.ext.valueView',
			),
		),

		'valueFormatters' => $moduleTemplate + array(
			'scripts' => array(
				'valueFormatters.js',
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

		'valueFormatters.ValueFormatterFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ValueFormatterFactory.js',
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
