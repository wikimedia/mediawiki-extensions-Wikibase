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
				'mediawiki',
				'valueFormatters',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterStore',
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
				'jquery',
				'util.inherit',
				'valueFormatters',
			),
		),

		'valueFormatters.ValueFormatterStore' => $moduleTemplate + array(
			'scripts' => array(
				'ValueFormatterStore.js',
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
				'jquery',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

	);

} );
