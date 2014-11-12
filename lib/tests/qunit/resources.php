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

		'wikibase.experts.EntityIdInput.tests' => $moduleBase + array(
			'scripts' => array(
				'experts/EntityIdInput.tests.js',
			),
			'dependencies' => array(
				'wikibase.experts.EntityIdInput',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'wikibase.api.RepoApi.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.RepoApi/wikibase.RepoApi.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.getLocationAgnosticMwApi',
				'wikibase.api.RepoApi',
			),
		),

		'wikibase.api.RepoApiError.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.RepoApi/wikibase.RepoApiError.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.api.RepoApiError',
			),
			'messages' => array(
				'wikibase-error-unexpected',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-client-error',
			),
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
		include( __DIR__ . '/jquery.wikibase/resources.php' ),
		include( __DIR__ . '/jquery.wikibase-shared/resources.php' )
	);

} );
