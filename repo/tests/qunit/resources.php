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
	$packageFilesModuleBase = [
		'localBasePath' => dirname( dirname( __DIR__ ) ),
		'remoteExtPath' => 'Wikibase/repo/',
	];

	return [
		'jquery.wikibase.entitysearch.tests' => $packageFilesModuleBase + [
			'packageFiles' => [
				'tests/qunit/jquery.wikibase/jquery.wikibase.entitysearch.tests.js',
				'resources/jquery.wikibase/jquery.wikibase.entitysearch.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityselector',
			],
		],

		'wikibase.dataTypes.DataType.tests' => [
			'packageFiles' => [
				'tests/qunit/dataTypes/DataType.tests.js',

				'resources/dataTypes/DataType.js',
			],
			'localBasePath' => dirname( dirname( __DIR__ ) ),
			'remoteExtPath' => 'Wikibase/repo',
		],

		'wikibase.dataTypes.DataTypeStore.tests' => [
			'packageFiles' => [
				'tests/qunit/dataTypes/DataTypeStore.tests.js',

				'resources/dataTypes/DataTypeStore.js',
				'resources/dataTypes/DataType.js',
			],
			'localBasePath' => dirname( dirname( __DIR__ ) ),
			'remoteExtPath' => 'Wikibase/repo',
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

		'wikibase.EntityInitializer.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.EntityInitializer.tests.js',
			],
			'dependencies' => [
				'wikibase.EntityInitializer'
			],
		],
	];
} );
