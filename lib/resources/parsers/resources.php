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

		'wikibase.parsers' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.parsers.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery',
				'valueParsers.parsers',
				'valueParsers.ValueParserStore',
				'wikibase.datamodel',
				'wikibase.EntityIdParser'
			),
		),

	);

} );
