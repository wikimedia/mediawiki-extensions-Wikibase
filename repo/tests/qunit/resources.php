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

	$modules = [

		'jquery.wikibase.entitysearch.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase/jquery.wikibase.entitysearch.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entitysearch',
			],
		],

		'wikibase.dataTypeStore.tests' => $moduleBase + [
			'scripts' => [
				'dataTypes/wikibase.dataTypeStore.tests.js',
			],
			'dependencies' => [
				'dataTypes.DataTypeStore',
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

	return $modules;

} );
