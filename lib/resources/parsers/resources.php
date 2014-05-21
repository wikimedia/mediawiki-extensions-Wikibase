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

		'wikibase.parsers.api' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.parsers.api.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.RepoApi'
			),
		),

		'wikibase.ApiBasedValueParser' => $moduleTemplate + array(
			'scripts' => array(
				'ApiBasedValueParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers.ValueParser',
				'wikibase.parsers.api',
			),
		),

		'wikibase.EntityIdParser' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers.ValueParser',
				'wikibase',
				'wikibase.datamodel',
			),
		),

		'wikibase.GlobeCoordinateParser' => $moduleTemplate + array(
			'scripts' => array(
				'GlobeCoordinateParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.ApiBasedValueParser',
			),
		),

		'wikibase.QuantityParser' => $moduleTemplate + array(
			'scripts' => array(
				'QuantityParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.ApiBasedValueParser',
			),
		),

		'wikibase.TimeParser' => $moduleTemplate + array(
			'scripts' => array(
				'TimeParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.ApiBasedValueParser',
			),
		),

		'wikibase.MonolingualTextParser' => $moduleTemplate + array(
			'scripts' => array(
				'MonolingualTextParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.ApiBasedValueParser',
			),
		),

		'wikibase.parsers' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.parsers.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueParsers.parsers',
				'valueParsers.ValueParserStore',
				'wikibase.datamodel',
				'wikibase.EntityIdParser',
				'wikibase.GlobeCoordinateParser',
				'wikibase.QuantityParser',
				'wikibase.TimeParser',
				'wikibase.MonolingualTextParser',
			),
		),

	);

} );
