<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(

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
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

	);

} );
