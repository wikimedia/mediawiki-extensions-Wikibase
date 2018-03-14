<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/repo/tests/qunit',
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
