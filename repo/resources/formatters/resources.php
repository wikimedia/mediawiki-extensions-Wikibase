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

		'wikibase.formatters.ApiBasedValueFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'ApiBasedValueFormatter.js',
			),
			'dependencies' => array(
				'wikibase',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

		'wikibase.formatters.ApiFormatterFactory' => $moduleTemplate + array(
			'scripts' => array(
				'ApiFormatterFactory.js',
			),
			'dependencies' => array(
				'wikibase.api.FormatValueCaller',
				'wikibase.formatters.ApiBasedValueFormatter',
				'wikibase.ValueFormatterFactory'
			),
		),

	);

} );
