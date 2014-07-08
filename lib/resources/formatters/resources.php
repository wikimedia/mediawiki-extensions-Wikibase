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
