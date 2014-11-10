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

		'wikibase.formatters.getApiBasedValueFormatterConstructor' => $moduleTemplate + array(
			'scripts' => array(
				'getApiBasedValueFormatterConstructor.js',
			),
			'dependencies' => array(
				'wikibase.dataTypes',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

		'wikibase.formatters.getStore' => $moduleTemplate + array(
			'scripts' => array(
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterStore',
				'wikibase.api.FormatValueCaller',
				'wikibase.datamodel',
				'wikibase.formatters.getApiBasedValueFormatterConstructor',
			),
		),

	);

} );
