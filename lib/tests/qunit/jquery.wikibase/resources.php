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

		'jquery.wikibase.aliasesview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.aliasesview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.aliasesview',
			),
		),

		'jquery.wikibase.badgeselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.badgeselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.badgeselector',
				'mediawiki.Title',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.store.FetchedContent',
			),
		),

		'jquery.wikibase.claimgrouplabelscroll.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.claimgrouplabelscroll.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.claimgrouplabelscroll',
			),
		),

		'jquery.wikibase.claimview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.claimview.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview',
				'jquery.wikibase.claimview',
				'mediawiki.Title',
				'valueFormatters',
				'wikibase.AbstractedRepoApi',
				'wikibase.datamodel',
				'wikibase.RepoApi',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
			),
		),

		'jquery.wikibase.descriptionview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.descriptionview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.descriptionview',
			),
		),

		'jquery.wikibase.entityview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entityview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityview',
				'wikibase.datamodel'
			),
		),

		'jquery.wikibase.entityselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entityselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityselector',
			),
		),

		'jquery.wikibase.fingerprintgroupview.tests' => $moduleBase + array(
				'scripts' => array(
					'jquery.wikibase.fingerprintgroupview.tests.js',
				),
				'dependencies' => array(
					'jquery.wikibase.fingerprintgroupview',
				),
			),

		'jquery.wikibase.fingerprintlistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.fingerprintlistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.fingerprintlistview',
			),
		),

		'jquery.wikibase.fingerprintview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.fingerprintview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.fingerprintview',
			),
		),

		'jquery.wikibase.labelview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.labelview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.labelview',
			),
		),

		'jquery.wikibase.listview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.listview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.listview',
			),
		),

		'jquery.wikibase.pagesuggester.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.pagesuggester.tests.js'
			),
			'dependencies' => array(
				'jquery.wikibase.pagesuggester',
			),
		),

		'jquery.wikibase.referenceview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.referenceview.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertStore',
				'jquery.wikibase.referenceview',
				'mediawiki.Title',
				'wikibase.AbstractedRepoApi',
				'wikibase.datamodel',
				'wikibase.RepoApi',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
				'valueFormatters'
			),
		),

		'jquery.wikibase.sitelinkgrouplistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgrouplistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkgrouplistview',
				'wikibase.datamodel',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinkgroupview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgroupview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkgroupview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinklistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinklistview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinkview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.siteselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.siteselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.siteselector',
				'wikibase.Site',
			),
		),

		'jquery.wikibase.snaklistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.snaklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snaklistview',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
				'wikibase.datamodel',
				'mediawiki.Title',
				'jquery.valueview',
				'valueFormatters'
			),
		),

		'jquery.wikibase.statementview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementview',
				'wikibase.datamodel'
			),
		),

		'jquery.wikibase.statementview.RankSelector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementview.RankSelector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementview',
				'wikibase.datamodel',
			),
		),

		'jquery.wikibase.wbtooltip.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.wbtooltip.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.wbtooltip',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/toolbar/resources.php' )
	);

} );
