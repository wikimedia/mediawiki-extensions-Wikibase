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
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(
		'jquery.wikibase.entitysearch.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.entitysearch.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entitysearch',
			),
		),

		'wikibase.dataTypeStore.tests' => $moduleBase + array(
			'scripts' => array(
				'dataTypes/wikibase.dataTypeStore.tests.js',
			),
			'dependencies' => array(
				'dataTypes.DataTypeStore',
				'wikibase.dataTypeStore',
			),
		),

		'wikibase.experts.Item.tests' => $moduleBase + array(
			'scripts' => array(
				'experts/Item.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.tests.testExpert',
				'wikibase.experts.Item',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'wikibase.experts.Property.tests' => $moduleBase + array(
			'scripts' => array(
				'experts/Property.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.tests.testExpert',
				'wikibase.experts.Property',
				'wikibase.tests.qunit.testrunner',
			),
		),
	);
} );
