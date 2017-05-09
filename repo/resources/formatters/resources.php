<?php

/**
 * @license GPL-2.0+
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
		'wikibase.formatters.ApiValueFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'ApiValueFormatter.js',
			),
			'dependencies' => array(
				'wikibase',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

		'wikibase.formatters.ApiValueFormatterFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ApiValueFormatterFactory.js',
			),
			'dependencies' => array(
				'wikibase.api.FormatValueCaller',
				'wikibase.formatters.ApiValueFormatter',
				'wikibase.ValueFormatterFactory'
			),
		),
	);
} );
