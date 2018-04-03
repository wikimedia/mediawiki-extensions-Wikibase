<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Kreuz
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	return [
		'wikibase.parsers.getStore' => [
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Wikibase/repo/resources/parsers',
			'scripts' => [
				'getApiBasedValueParserConstructor.js',
				'getStore.js',
			],
			'dependencies' => [
				'dataValues',
				'dataValues.values',
				'util.inherit',
				'valueParsers.parsers',
				'valueParsers.ValueParser',
				'valueParsers.ValueParserStore',
				'wikibase',
				'wikibase.api.ParseValueCaller',
				'wikibase.datamodel',
			],
		],
	];
} );
