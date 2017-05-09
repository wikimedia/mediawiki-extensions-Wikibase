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

	$modules = array(
		'wikibase.tests.qunit.testrunner' => $moduleBase + array(
			'scripts' => 'data/testrunner.js',
			'dependencies' => array(
				'test.mediawiki.qunit.testrunner',
				'wikibase',
			),
			'position' => 'top'
		),

		'wikibase.Site.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.Site.tests.js',
			),
			'dependencies' => array(
				'wikibase.Site',
			),
		),

		'wikibase.sites.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.sites.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.Site',
				'wikibase.sites',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'wikibase.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.tests.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),
	);

	return array_merge(
		$modules,
		include __DIR__ . '/jquery.wikibase/resources.php'
	);
} );
