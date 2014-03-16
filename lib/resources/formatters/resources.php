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
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(

		'wikibase.formatters.api' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.formatters.api.js',
			),
			'dependencies' => array(
				'mediawiki.api',
				'wikibase',
				'wikibase.dataTypes'
			),
		),

		'wikibase.ApiBasedValueFormatter' => $moduleTemplate + array(
			'scripts' => array(
				'ApiBasedValueFormatter.js',
			),
			'dependencies' => array(
				'mediawiki.api',
				'util.inherit',
				'valueFormatters.ValueFormatter',
				'wikibase.formatters.api',
			),
		),

		'wikibase.formatters' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.formatters.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterFactory',
				'wikibase.ApiBasedValueFormatter',
				'wikibase.datamodel',
				'wikibase.dataTypes',
			),
		),

	);

} );
