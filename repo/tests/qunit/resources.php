<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [
		'jquery.wikibase.entitysearch.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase/jquery.wikibase.entitysearch.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entitysearch',
			],
		],

		'wikibase.dataTypes.DataType.tests' => $moduleBase + [
			'scripts' => [
				'dataTypes/DataType.tests.js',
			],
			'dependencies' => [
				'wikibase.dataTypes.DataType'
			],
		],

		'wikibase.dataTypes.DataTypeStore.tests' => $moduleBase + [
			'scripts' => [
				'dataTypes/DataTypeStore.tests.js',
			],
			'dependencies' => [
				'wikibase.dataTypes.DataTypeStore'
			],
		],

		'wikibase.dataTypeStore.tests' => $moduleBase + [
			'scripts' => [
				'dataTypes/wikibase.dataTypeStore.tests.js',
			],
			'dependencies' => [
				'wikibase.dataTypes.DataTypeStore',
				'wikibase.dataTypeStore',
			],
		],

		'wikibase.experts.Item.tests' => $moduleBase + [
			'scripts' => [
				'experts/Item.tests.js',
			],
			'dependencies' => [
				'jquery.valueview.tests.testExpert',
				'wikibase.experts.Item',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'wikibase.experts.Property.tests' => $moduleBase + [
			'scripts' => [
				'experts/Property.tests.js',
			],
			'dependencies' => [
				'jquery.valueview.tests.testExpert',
				'wikibase.experts.Property',
				'wikibase.tests.qunit.testrunner',
			],
		],
	];
} );
